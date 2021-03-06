/*
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
(<template>
    <div class="labeled-items-list">
        <div v-if="loading" class="labeled-items-loading"></div>
        <div v-if="error" class="tlp-alert-danger labeled-items-error">{{ error }}</div>
        <div class="empty-pane-text" v-if="empty && ! loading && ! error">{{ empty_message }}</div>
        <LabeledItem v-for="item in items"
                     v-bind:item="item"
                     v-bind:key="item.html_url"
        />
        <div class="labeled-items-list-more" v-if="has_more_items">
            <button class="tlp-button-primary tlp-button-outline" v-on:click="loadMore">
                <i class="tlp-button-icon fa fa-spinner fa-spin" v-if="is_loading_more"></i>
                {{ load_more }}
            </button>
        </div>
    </div>
</template>)
(<script>
import LabeledItem          from './LabeledItem.vue';
import { getLabeledItems }  from './rest-querier.js';
import { gettext_provider } from './gettext-provider.js';

export default {
    name: 'LabeledItemsList',
    components: { LabeledItem },
    props: {
        labelsId: String,
        projectId: String
    },
    data() {
        return {
            items: [],
            loading: true,
            error: false,
            are_there_items_user_cannot_see: false,
            offset: 0,
            limit: 50,
            has_more_items: false,
            is_loading_more: false
        };
    },
    computed: {
        labels_id() {
            return JSON.parse(this.labelsId);
        },
        empty() {
            return this.items.length === 0;
        },
        empty_message() {
            if (this.are_there_items_user_cannot_see) {
                return gettext_provider.gettext("There are no items you can see");
            }
            return gettext_provider.ngettext(
                "There isn't any item corresponding to label",
                "There isn't any item corresponding to labels",
                this.labels_id.length
            );
        },
        load_more() {
            return gettext_provider.gettext("Load more");
        }
    },
    mounted() {
        this.loadLabeledItems();
    },
    methods: {
        async loadLabeledItems() {
            if (this.labels_id.length === 0) {
                this.error   = gettext_provider.gettext("Please select one or more labels by editing this widget");
                this.loading = false;
                return;
            }

            try {
                const {
                    labeled_items,
                    are_there_items_user_cannot_see,
                    has_more,
                    offset
                } = await getLabeledItems(
                    this.projectId,
                    this.labels_id,
                    this.offset,
                    this.limit
                );

                this.offset         = offset;
                this.has_more_items = has_more;
                this.items          = this.items.concat(labeled_items);

                this.are_there_items_user_cannot_see = are_there_items_user_cannot_see;
            } catch (e) {
                const { error } = await e.response.json();
                this.error      = error.code + ' ' + error.message;
            } finally {
                this.loading = false;
            }
        },
        async loadMore() {
            this.is_loading_more = true;

            this.offset += this.limit;
            await this.loadLabeledItems();

            this.is_loading_more = false;
        }
    }
};
</script>)
