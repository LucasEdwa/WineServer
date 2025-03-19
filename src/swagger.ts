import swaggerJsdoc from "swagger-jsdoc";
import eventDocs from "./swagger/eventDocs";
import bookingDocs from "./swagger/bookingDocs";

const options = {
  definition: {
    openapi: "3.0.0",
    info: {
      title: "Wine Events API",
      version: "1.0.0",
      description: "API documentation for Wine Events application",
    },
    servers: [
      {
        url: "http://localhost:3000",
        description: "Development server",
      },
    ],
    paths: {
      "/api/getEventById/{eventId}": eventDocs.getEventById,
      "/api/createEvent": eventDocs.createEvent,
      "/api/editEvent/{eventId}": eventDocs.editEvent,
      "/api/deleteEvent/{eventId}": eventDocs.deleteEvent,
      "/api/createUserAndBooking": bookingDocs.createUserAndBooking,
      "/api/getBookings": bookingDocs.getBookings,
      "/api/getBookingByUser/{useremail}": bookingDocs.getBookingByUser,
      "/api/editBooking/{bookingId}": bookingDocs.editBookingByBookingId,
    },
  },
  apis: [], // We're not using JSDoc comments anymore
};

export const specs = swaggerJsdoc(options);
