import express from "express";
import fileUpload, { UploadedFile } from "express-fileupload";
import { Request, Response } from "express";
import { format } from "date-fns"; // Import date-fns for date formatting

import pool from "../connection";
import { createEvent, editEvent, deleteEvent } from "../services/eventService";
import { TBooking, TEvent, Error, TUser } from "../models/types";
import { TWineCollection } from "../models/types";
import { TActivity } from "../models/types";
import { TMaterial } from "../models/types";

const router = express.Router();

router.use(
  fileUpload({
    limits: { fileSize: 50 * 1024 * 1024 },
    useTempFiles: true,
    tempFileDir: "/tmp/",
  })
);

router.post(
  "/createUserAndBooking",
  async (
    req: Request,
    res: Response<{ message: string } | Error>
  ): Promise<void> => {
    const {
      eventId,
      eventTitle,
      ...userProps
    }: TUser & TBooking & { eventId: number; eventTitle: string } = req.body;

    const connection = await pool.getConnection();
    try {
      await connection.beginTransaction();

      // Check if the email is already associated with the event
      const [existingBooking] = await connection.query(
        `SELECT * FROM Bookings 
         JOIN users ON Bookings.userId = users.id 
         WHERE users.email = ? AND Bookings.eventId = ?`,
        [userProps.email, eventId]
      );

      if ((existingBooking as any).length > 0) {
        res.status(400).send({ message: "Email already exists for this event" });
        return;
      }

      // Retrieve event details
      const [eventResult] = await connection.query(
        "SELECT capacity, date FROM events WHERE id = ?",
        [eventId]
      );
      const event = (eventResult as any)[0];

      if (!event || event.capacity <= 0) {
        res.status(400).send({ error: "Event is fully booked" });
        return;
      }

      // Format the event date for MySQL
      const formattedDate = format(new Date(event.date), "yyyy-MM-dd");

      // Create user
      const [userResult] = await connection.query(
        "INSERT INTO users (firstName, lastName, email, phone) VALUES (?, ?, ?, ?)",
        [
          userProps.firstName,
          userProps.lastName,
          userProps.email,
          userProps.phone,
        ]
      );
      const userId = (userResult as any).insertId;

      // Create booking
      const bookingProps: TBooking = {
        id: 0, // Placeholder, as the database will generate the ID
        userId,
        eventId,
        eventTitle,
        date: formattedDate, // Use the formatted date
      };
      await connection.query(
        "INSERT INTO Bookings (userId, eventId, eventTitle, date) VALUES (?, ?, ?, ?)",
        [
          bookingProps.userId,
          bookingProps.eventId,
          bookingProps.eventTitle,
          bookingProps.date,
        ]
      );

      // Decrement event capacity
      await connection.query(
        "UPDATE events SET capacity = capacity - 1 WHERE id = ?",
        [eventId]
      );

      await connection.commit();
      res
        .status(201)
        .send({ message: "User and booking created successfully" });
    } catch (error) {
      console.error("Error:", error);
      await connection.rollback();
      res.status(500).send({ error: "Error creating user and booking" });
    } finally {
      connection.release();
    }
  }
);

router.get(
  "/getBookingByUser/:useremail",
  async (req: Request, res: Response<TBooking[] | Error>): Promise<void> => {
    const { useremail } = req.params;
    const connection = await pool.getConnection();
    try {
      const [results] = await connection.query(
        `SELECT 
                users.firstName,
                users.lastName,
                users.email,
                users.phone,
                Bookings.eventTitle,
                events.date
            FROM Bookings
            JOIN users ON Bookings.userId = users.id
            JOIN events ON Bookings.eventId = events.id
            WHERE users.email = ?`,
        [useremail]
      );
      res.status(200).send(results as TBooking[]);
    } catch (error) {
      console.error("Error:", error);
      res.status(500).send({ error: "Error retrieving bookings" });
    } finally {
      connection.release();
    }
  }
);

router.delete(
  "/deleteBooking/:bookingId",
  async (
    req: Request,
    res: Response<{ message: string } | Error>
  ): Promise<void> => {
    const { bookingId } = req.params;

    const connection = await pool.getConnection();
    try {
      await connection.query("DELETE FROM Bookings WHERE id = ?", [bookingId]);
      res.status(200).send({ message: "Booking deleted successfully" });
    } catch (error) {
      console.error("Error:", error); // Add logging
      res.status(500).send({ error: "Error deleting booking" });
    } finally {
      connection.release();
    }
  }
);

router.get(
  "/getEvents",
  async (req: Request, res: Response<TEvent[] | Error>): Promise<void> => {
    const connection = await pool.getConnection();
    try {
      const [results] = await connection.query(
        `SELECT 
          id, title, description, imageUrl, date, startTime, endTime, location, 
          capacity, price 
         FROM events`
      );
      res.status(200).send(results as TEvent[]);
    } catch (error) {
      console.error("Error:", error);
      res.status(500).send({ error: "Error retrieving events" });
    } finally {
      connection.release();
    }
  }
);
// filepath: /Users/lucaseduardo/wineServer/src/routes/router.ts
router.get(
  "/getEventById/:eventId",
  async (req: Request, res: Response<TEvent | { error: string }>): Promise<void> => {
    const { eventId } = req.params;
    const connection = await pool.getConnection();
    try {
      const [eventResults] = await connection.query(
        "SELECT * FROM events WHERE id = ?",
        [eventId]
      );

      if ((eventResults as TEvent[]).length === 0) {
        res.status(404).send({ error: "Event not found" });
        return;
      }

      const event = (eventResults as TEvent[])[0];

      // Fetch wineCollection for the event
      const [wineResults] = await connection.query(
        "SELECT * FROM wineCollection WHERE eventId = ?",
        [eventId]
      );

      // Fetch activities for the event
      const [activityResults] = await connection.query(
        "SELECT * FROM activities WHERE eventId = ?",
        [eventId]
      );

      event.wineCollection = (wineResults as TWineCollection[]) || [];
      event.activities = (activityResults as TActivity[]) || [];

      // Fetch materials for each activity using Promise.all
      event.activities = await Promise.all(
        event.activities.map(async (activity: TActivity) => {
          const [materialResults] = await connection.query(
            "SELECT * FROM materials WHERE activityId = ?",
            [activity.id]
          );
          activity.materials = (materialResults as TMaterial[]) || [];
          return activity;
        })
      );

      res.status(200).send(event);
    } catch (error) {
      console.error("Error:", error);
      res.status(500).send({ error: "Error retrieving event" });
    } finally {
      connection.release();
    }
  }
);
router.delete(
  "/api/deleteEvent/:eventId", // Update the route path to match the frontend request
  async (req: Request, res: Response<{ message: string } | Error>): Promise<void> => {
    const { eventId } = req.params;
    const connection = await pool.getConnection();
    try {
      await connection.beginTransaction();
      await connection.query("DELETE FROM events WHERE id = ?", [eventId]);
      await connection.query("DELETE FROM Bookings WHERE eventId = ?", [eventId]);
      await connection.commit();
      res.status(200).send({ message: "Event deleted successfully" });
    } catch (error) {
      console.error("Error:", error);
      await connection.rollback();
      res.status(500).send({ error: "Error deleting event" });
    } finally {
      connection.release();
    }
  }
);

export default router;
