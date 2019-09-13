export default {
    data() {
        return {
            originalValue: this.field.value
        }
    },
    created() {

        const resource = this.field.resources.find(resource => resource.className === this.field.value)

        this.field.value = resource.label

    }
}
