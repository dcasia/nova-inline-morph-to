<template>

    <div>

        <panel :panel="{ name: field.value, fields: standardFields }"/>

        <resource-index v-for="(hasOneField, index) of hasOneFields"
                        :key="index"
                        :field="hasOneField"
                        :resource-name="hasOneField.resourceName"
                        :via-resource="hasOneField.inlineMorphTo.viaResource"
                        :via-resource-id="hasOneField.inlineMorphTo.viaResourceId"
                        :via-relationship="hasOneField.hasOneRelationship"
                        :relationship-type="'hasOne'"
                        :load-cards="false"/>

    </div>

</template>

<script>

    import ReplaceValueWithLabel from '../ReplaceValueWithLabel'

    export default {
        props: ['resource', 'resourceName', 'resourceId', 'field'],
        mixins: [ReplaceValueWithLabel],
        computed: {
            hasOneFields() {

                return this.fields.filter(field => field.component === 'has-one-field')

            },
            standardFields() {

                return this.fields.filter(field => field.component !== 'has-one-field')

            },
            fields() {

                return this.field.resources.find(resource => resource.className === this.originalValue).fields

            }
        }
    }

</script>
