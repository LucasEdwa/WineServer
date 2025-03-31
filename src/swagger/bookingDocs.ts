const bookingDocs = {
    createUserAndBooking: {
        post: {
            summary: 'Create a new user and booking',
            requestBody: {
                required: true,
                content: {
                    'application/json': {
                        schema: {
                            type: 'object',
                            properties: {
                                firstName: { type: 'string' },
                                lastName: { type: 'string' },
                                email: { type: 'string' },
                                phone: { type: 'string' },
                                eventId: { type: 'number' },
                                eventTitle: { type: 'string' }
                            },
                            required: ['firstName', 'lastName', 'email', 'phone', 'eventId', 'eventTitle']
                        }
                    }
                }
            },
            responses: {
                201: { description: 'User and booking created successfully' },
                500: { description: 'Server error' }
            }
        }
    },
    getBookings: {
        get: {
            summary: 'Get all bookings',
            responses: {
                200: { description: 'Bookings retrieved successfully' },
                500: { description: 'Server error' }
            }
        }
    },
    getBookingByUser: {
        get: {
            summary: 'Get booking by user',
            parameters: [{
                in: 'path',
                name: 'useremail',
                required: true,
                schema: { type: 'string' },
                description: 'User email'
            }],
            responses: {
                200: { description: 'Booking retrieved successfully' },
                500: { description: 'Server error' }
            }
        }
    },
    editBookingByBookingId: {
        put: {
            summary: 'Edit booking by booking ID',
            parameters: [{
                in: 'path',
                name: 'bookingId',
                required: true,
                schema: { type: 'number' },
                description: 'Booking ID'
            }],
            requestBody: {
                required: true,
                content: {
                    'application/json': {
                        schema: {
                            type: 'object',
                            properties: {
                                firstName: { type: 'string' },
                                lastName: { type: 'string' },
                                email: { type: 'string' },
                                phone: { type: 'string' },
                                eventId: { type: 'number' },
                                eventTitle: { type: 'string' }
                            },
                            required: ['firstName', 'lastName', 'email', 'phone', 'eventId', 'eventTitle']
                        }
                    }
                }
            },
            responses: {
                200: { description: 'Booking updated successfully' },
                500: { description: 'Server error' }
            }
        }
    },
    deleteBooking: {
        delete: {
            summary: 'Delete a booking by ID',
            parameters: [{
                in: 'path',
                name: 'bookingId',
                required: true,
                schema: { type: 'number' },
                description: 'Booking ID'
            }],
            responses: {
                200: { description: 'Booking deleted successfully' },
                500: { description: 'Server error' }
            }
        }
    }
};

export default bookingDocs;