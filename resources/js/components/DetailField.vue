<template>

    <PanelItem :index="index" :field="field" :field-name="field.name">

        <template #value>

            <div v-for="({ fields }) in field.value.panels" class="mb-4">

                <div v-if="fields.length > 0" :class="[
                    'relative overflow-hidden bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 shadow',
                    'py-2 px-6 divide-y divide-gray-100 dark:divide-gray-700'
                ]">

                    <component
                        :key="index"
                        v-for="(field, index) in fields"
                        :index="index"
                        :is="resolveComponentName(field)"
                        :field="field"
                    />

                </div>

            </div>

        </template>

    </PanelItem>

</template>

<script>

    import isNil from 'lodash/isNil'
    import PanelItem from './PanelItem'

    export default {
        props: [ 'index', 'resource', 'resourceName', 'resourceId', 'field' ],
        components: { PanelItem },
        methods: {
            resolveComponentName(panel) {
                return isNil(panel.prefixComponent) || panel.prefixComponent
                    ? 'detail-' + panel.component
                    : panel.component
            },
        },
    }

</script>
