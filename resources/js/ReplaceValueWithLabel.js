export default {
    data() {
        return {
            originalValue: this.field.value
        }
    },
    computed: {
        label() {
            return this.field.resources.find(resource => resource.className === this.field.value).label
        }
    }
}
