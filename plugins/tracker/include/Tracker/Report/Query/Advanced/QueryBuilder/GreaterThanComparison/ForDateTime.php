<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\GreaterThanComparison;

use CodendiDataAccess;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\FromWhere;
use Tuleap\Tracker\Report\Query\Advanced\FromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\DateTimeFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonFieldBuilder;

class ForDateTime implements FromWhereBuilder, ValueWrapperVisitor
{
    /**
     * @var DateTimeValueRounder
     */
    private $date_time_value_rounder;
    /**
     * @var FromWhereComparisonFieldBuilder
     */
    private $from_where_builder;

    public function __construct(
        DateTimeValueRounder $date_time_value_rounder,
        FromWhereComparisonFieldBuilder $from_where_comparison_builder
    ) {
        $this->date_time_value_rounder = $date_time_value_rounder;
        $this->from_where_builder      = $from_where_comparison_builder;
    }

    /**
     * @return FromWhere
     */
    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $suffix   = spl_object_hash($comparison);
        $value = $comparison->getValueWrapper()->accept($this, new ValueWrapperParameters($field));
        $field_id = (int) $field->getId();

        $changeset_value_date_alias = "CVDate_{$field_id}_{$suffix}";
        $changeset_value_alias      = "CV_{$field_id}_{$suffix}";

        $ceiled_timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($value);
        $ceiled_timestamp = $this->escapeInt($ceiled_timestamp);
        $condition        = "$changeset_value_date_alias.value > $ceiled_timestamp";

        return $this->from_where_builder->getFromWhere(
            $field_id,
            $changeset_value_alias,
            $changeset_value_date_alias,
            'tracker_changeset_value_date',
            $condition
        );
    }

    /**
     * @return string
     */
    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $value_wrapper->getValue();
    }

    /**
     * @return string
     */
    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        $field = $parameters->getField();
        if ($field->isTimeDisplayed() === true) {
            return $value_wrapper->getValue()->format(DateTimeFieldChecker::DATETIME_FORMAT);
        }
        return $value_wrapper->getValue()->format(DateFieldChecker::DATE_FORMAT);
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
    }

    private function escapeInt($value)
    {
        return CodendiDataAccess::instance()->escapeInt($value);
    }
}