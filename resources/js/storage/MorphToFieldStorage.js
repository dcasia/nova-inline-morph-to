export default {
    fetchAvailableResources(resourceName, fieldAttribute, options) {
        if (resourceName === undefined || fieldAttribute == undefined || options == undefined) {
            throw new Error('please pass the right things')
        }
        
        return Nova.request().get(`/nova-vendor/inline-morph-to/${ resourceName }/morphable/${ fieldAttribute }`, options)
    },
    
    determineIfSoftDeletes(resourceType) {
        return Nova.request().get(`/nova-api/${ resourceType }/soft-deletes`)
    },
}
