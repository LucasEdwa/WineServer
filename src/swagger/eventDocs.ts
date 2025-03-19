const eventDocs = {
    getEventById: {
        get: {
            summary: 'Get event by ID',
            parameters: [{
                in: 'path',
                name: 'eventId',
                required: true,
                schema: {
                    type: 'integer'
                },
                description: 'ID of the event'
            }],
            responses: {
                200: {
                    description: 'Event details'
                },
                500: {
                    description: 'Server error'
                }
            }
        }
    },
    createEvent: {
        post: {
            summary: 'Create a new event',
            requestBody: {
                required: true,
                content: {
                    'multipart/form-data': {
                        schema: {
                            type: 'object',
                            properties: {
                                image: {
                                    type: 'string',
                                    format: 'binary'
                                },
                                title: {
                                    type: 'string'
                                },
                                description: {
                                    type: 'string'
                                },
                                date: {
                                    type: 'string'
                                },
                                startTime: {
                                    type: 'string'
                                },
                                endTime: {
                                    type: 'string'
                                },
                                location: {
                                    type: 'string'
                                },
                                capacity: {
                                    type: 'number'
                                },
                                price: {
                                    type: 'number'
                                },
                                isPrivate: {
                                    type: 'boolean'
                                },
                              
                            }
                        }
                    }
                }
            },
            responses: {
                201: {
                    description: 'Event created successfully'
                },
                500: {
                    description: 'Server error'
                }
            }
        }
    },
    editEvent: {
        put: {
            summary: 'Edit an event',
            parameters: [{
                in: 'path',
                name: 'eventId',
                required: true,
                schema: {
                    type: 'integer'
                },
                description: 'ID of the event'
            }],
            requestBody: {
                required: true,
                content: {
                    'multipart/form-data': {
                        schema: {
                            type: 'object',
                            properties: {
                                image: {
                                    type: 'string',
                                    format: 'binary'
                                },
                                title: {
                                    type: 'string'
                                },
                                description: {
                                    type: 'string'
                                },
                                date: {
                                    type: 'string'
                                },
                                startTime: {
                                    type: 'string'
                                },
                                endTime: {
                                    type: 'string'
                                },
                                location: {
                                    type: 'string'
                                },
                                capacity: {
                                    type: 'number'
                                },
                                price: {
                                    type: 'number'
                                },
                                isPrivate: {
                                    type: 'boolean'
                                }
                            }
                        }
                    }
                },
                responses: {    
                    200: {
                        description: 'Event updated successfully'
                    },
                    500: {
                        description: 'Server error'
                    }
                }
            }
        }
    },
    deleteEvent: {
        delete: {
            summary: 'Delete an event',
            parameters: [{
                in: 'path',
                name: 'eventId',    
                required: true,
                schema: {
                    type: 'integer'
                },
                description: 'ID of the event'
            }],
            responses: {
                200: {
                    description: 'Event deleted successfully'
                },
                500: {
                    description: 'Server error'
                }
            }
        }
    }
}

export default eventDocs; 