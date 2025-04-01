import express from 'express';
import fileUpload, { UploadedFile } from 'express-fileupload';
import { Request, Response } from 'express';

import pool from '../connection';
import path from 'path';
import { createEvent, editEvent, deleteEvent } from '../services/eventService';
import { TBooking , TEvent ,Error, TUser } from '../models/types';

const router = express.Router();

router.use(fileUpload({
    limits: { fileSize: 50 * 1024 * 1024 },
    useTempFiles: true,
    tempFileDir: '/tmp/'
}));

router.post('/createUserAndBooking', async (req: Request, res: Response<{ message: string } | Error>): Promise<void> => {
    const { 
        eventId, 
        eventTitle, 
        ...userProps 
    }: TUser & TBooking & { eventId: number; eventTitle: string } = req.body;

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        // Retrieve event date
        const [eventResult] = await connection.query(
            'SELECT date FROM events WHERE id = ?',
            [eventId]
        );
        const eventDate = (eventResult as any)[0].date;

        // Create user
        const [userResult] = await connection.query(
            'INSERT INTO users (firstName, lastName, email, phone) VALUES (?, ?, ?, ?)',
            [userProps.firstName, userProps.lastName, userProps.email, userProps.phone]
        );
        const userId = (userResult as any).insertId;

        // Create booking
        const bookingProps: TBooking = {
            id: 0, // Placeholder, as the database will generate the ID
            userId,
            eventId,
            eventTitle,
            date: eventDate
        };
        await connection.query(
            'INSERT INTO Bookings (userId, eventId, eventTitle, date) VALUES (?, ?, ?, ?)',
            [bookingProps.userId, bookingProps.eventId, bookingProps.eventTitle, bookingProps.date]
        );

        await connection.commit();
        res.status(201).send({ message: 'User and booking created successfully' });
    } catch (error) {
        console.error('Error:', error);
        await connection.rollback();
        res.status(500).send({ error: 'Error creating user and booking' });
    } finally {
        connection.release();
    }
});

router.get('/getBookings', async (req: Request, res: Response<TBooking[] | Error>): Promise<void> => {
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
            JOIN events ON Bookings.eventId = events.id`
        );
        res.status(200).send(results as TBooking[]);
    } catch (error) {
        console.error('Error:', error);
        res.status(500).send({ error: 'Error retrieving bookings' });
    } finally {
        connection.release();
    }
});

router.get('/getBookingByUser/:useremail', async (req: Request, res: Response<TBooking[] | Error>): Promise<void> => {
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
        console.error('Error:', error);
        res.status(500).send({ error: 'Error retrieving bookings' });
    } finally {
        connection.release();
    }
});

router.put('/editBooking/:bookingId', async (req: Request, res: Response<{ message: string } | Error>): Promise<void> => {
    const { bookingId } = req.params;
    const { 
        firstName, 
        lastName, 
        email, 
        phone, 
        eventId, 
        eventTitle 
    } = req.body;

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        // Get event date
        const [eventResult] = await connection.query(
            'SELECT date FROM events WHERE id = ?',
            [eventId]
        );
        const eventDate = (eventResult as any)[0].date;

        // Update user details
        await connection.query(
            'UPDATE users SET firstName = ?, lastName = ?, email = ?, phone = ? WHERE id = (SELECT userId FROM Bookings WHERE id = ?)',
            [firstName, lastName, email, phone, bookingId]
        );

        // Update booking details
        await connection.query(
            'UPDATE Bookings SET eventId = ?, eventTitle = ?, date = ? WHERE id = ?',
            [eventId, eventTitle, eventDate, bookingId]
        );

        await connection.commit();
        res.status(200).send({ message: 'Booking updated successfully' });
    } catch (error) {
        console.error('Error:', error);
        await connection.rollback();
        res.status(500).send({ error: 'Error updating booking' });
    } finally {
        connection.release();
    }
});

router.delete('/deleteBooking/:bookingId', async (req: Request, res: Response<{ message: string } | Error>): Promise<void> => {
    const { bookingId } = req.params;

    const connection = await pool.getConnection();
    try {
        await connection.query(
            'DELETE FROM Bookings WHERE id = ?',
            [bookingId]
        );
        res.status(200).send({ message: 'Booking deleted successfully' });
    } catch (error) {
        console.error('Error:', error); // Add logging
        res.status(500).send({ error: 'Error deleting booking' });
    } finally {
        connection.release();
    }
});

router.get('/getEvents', async (req: Request, res: Response<TEvent[] | Error>): Promise<void> => {
    const connection = await pool.getConnection();
    try {
        const [results] = await connection.query(
            'SELECT id, title, description, imageUrl, date, startTime, endTime, location, capacity, price, currentAttendees, isPrivate FROM events'
        );

        const events = (results as TEvent[]).map(event => {
            const currentDate = new Date();
            const eventDate = new Date(event.date);
            return {
                ...event,
                isActive: eventDate >= currentDate // Event is active if its date is in the future
            };
        });

        res.status(200).send(events);
    } catch (error) {
        console.error('Error:', error);
        res.status(500).send({ error: 'Error retrieving events' });
    } finally {
        connection.release();
    }
});

router.get('/getEventById/:eventId', async (req: Request, res: Response<TEvent[] | Error>): Promise<void> => {
    const { eventId } = req.params;
    const connection = await pool.getConnection();
    try {
        const [results] = await connection.query(
            'SELECT * FROM events WHERE id = ?',
            [eventId]
        );
        res.status(200).send(results as TEvent[]);
    } catch (error) {
        console.error('Error:', error);
        res.status(500).send({ error: 'Error retrieving event' });
    } finally {
        connection.release();
    }
});

router.post('/createEvent', async (req: Request, res: Response<{ success: boolean; message: string; eventId: number } | Error>): Promise<void> => {
    try {
        const eventId = await createEvent(req.files?.image as UploadedFile, req.body);
        
        res.status(201).json({
            success: true,
            message: 'Event created successfully',
            eventId
        });
    } catch (error) {
        console.error('Error:', error);
        res.status(500).send({ error: 'Error creating event' });
    }
});

router.put('/editEvent/:eventId', async (req: Request, res: Response<{ success: boolean; message: string; eventId: number } | Error>): Promise<void> => {
    try {
        const eventData = {
            ...req.body,
            image: req.files?.image as UploadedFile,
            currentImageUrl: req.body.imageUrl
        };
        
        const eventId = await editEvent(parseInt(req.params.eventId), eventData);
        res.status(200).json({
            success: true,
            message: 'Event updated successfully',
            eventId
        });
    } catch (error) {
        console.error('Error:', error);
        res.status(500).send({ error: 'Error updating event' });
    }
});

router.delete('/deleteEvent/:eventId', async (req: Request, res: Response<{ success: boolean; message: string } | Error>): Promise<void> => {
    const { eventId } = req.params;
    try {
        await deleteEvent(parseInt(eventId));
        res.status(200).json({
            success: true,
            message: 'Event deleted successfully'
        });
    } catch (error) {
        console.error('Error:', error);
        res.status(500).send({ error: 'Error deleting event' });
    }
});

export default router;
