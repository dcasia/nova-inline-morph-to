<template>

    <div>

        <panel-item :field="field"/>

        <panel-item v-for="resourceField in morphResource.fields" :field="resourceField"/>

    </div>

</template>

<script>

    import ReplaceValueWithLabel from '../ReplaceValueWithLabel'

    export default {
        props: ['resource', 'resourceName', 'resourceId', 'field'],
        mixins: [ReplaceValueWithLabel],
        computed: {
            morphResource() {

                return this.field.resources.find(resource => resource.className === this.originalValue)

            }
        }
    }

</script>
