<template>
    <span>{{ field.value }}</span>
</template>

<script>

    import ReplaceValueWithLabel from '../ReplaceValueWithLabel'

    export default {
        props: ['resourceName', 'field'],
        mixins: [ReplaceValueWithLabel]
    }

</script>
