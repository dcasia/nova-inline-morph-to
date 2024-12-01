<template>
    <span>{{ label }}</span>
</template>

<script>

    export default {
        props: [ 'field' ],
        computed: {
            label() {
                return this.field
                    .morphToTypes
                    .find(element => element.value === this.field.morphToType)
                    ?.display
            }
        }
    }

</script>
