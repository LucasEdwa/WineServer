import express, { Router, Request, Response } from 'express';
import fileUpload, { UploadedFile } from 'express-fileupload';
import { TBooking } from '../models/TBooking';
import { TUser } from '../models/TUser';
import pool from '../connection';
import path from 'path';

const router = Router();

router.use(fileUpload());

router.post('/createUserAndBooking', async (req, res) => {
    console.log('Request body:', req.body); // Add logging
    const { name, email, phone, eventId, eventTitle }: { name: string; email: string; phone: string; eventId: string; eventTitle: string } = req.body;

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
            'INSERT INTO users (name, email, phone) VALUES (?, ?, ?)',
            [name, email, phone]
        );
        const userId = (userResult as any).insertId;

        // Create booking
        await connection.query(
            'INSERT INTO Bookings (userId, eventId, date) VALUES (?, ?, ?)',
            [userId, eventId, eventDate]
        );

        await connection.commit();
        res.status(201).send({ message: 'User and booking created successfully' });
    } catch (error) {
        console.error('Error:', error); // Add logging
        await connection.rollback();
        res.status(500).send({ error: 'Error creating user and booking' });
    } finally {
        connection.release();
    }
});

router.get('/getBookings', async (req, res) => {
    const connection = await pool.getConnection();
    try {
        const [results] = await connection.query(
            `SELECT users.name, users.email, users.phone, events.title, events.date
            FROM Bookings
            JOIN users ON Bookings.userId = users.id
            JOIN events ON Bookings.eventId = events.id`
        );
        res.status(200).send(results);
    } catch (error) {
        console.error('Error:', error); // Add logging
        res.status(500).send({ error: 'Error retrieving bookings' });
    } finally {
        connection.release();
    }
});

router.get('/getBookingByUser/:useremail', async (req, res) => {
    const { useremail } = req.params;
    const connection = await pool.getConnection();
    try {
        const [results] = await connection.query(
            `SELECT users.name, users.email, users.phone, events.title, events.date
            FROM Bookings
            JOIN users ON Bookings.userId = users.id
            JOIN events ON Bookings.eventId = events.id
            WHERE users.email = ?`,
            [useremail]
        );
        res.status(200).send(results);
    } catch (error) {
        console.error('Error:', error); // Add logging
        res.status(500).send({ error: 'Error retrieving bookings' });
    } finally {
        connection.release();
    }
});

router.put('/editBooking/:bookingId', async (req, res) => {
    const { bookingId } = req.params;
    const { name, email, phone, title, date }: { name: string; email: string; phone: string; title: string; date: string } = req.body;

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        // Update user details
        await connection.query(
            'UPDATE users SET name = ?, email = ?, phone = ? WHERE id = (SELECT userId FROM Bookings WHERE id = ?)',
            [name, email, phone, bookingId]
        );

        // Update event details
        const [eventResult] = await connection.query(
            'SELECT id FROM events WHERE title = ?',
            [title]
        );
        const eventId = (eventResult as any)[0].id;

        // Update booking details
        await connection.query(
            'UPDATE Bookings SET eventId = ?, date = ? WHERE id = ?',
            [eventId, date, bookingId]
        );

        await connection.commit();
        res.status(200).send({ message: 'Booking updated successfully' });
    } catch (error) {
        console.error('Error:', error); // Add logging
        await connection.rollback();
        res.status(500).send({ error: 'Error updating booking' });
    } finally {
        connection.release();
    }
});

router.delete('/deleteBooking/:bookingId', async (req, res) => {
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


router.get('/getEvents', async (req, res) => {
    const connection = await pool.getConnection();
    try {
        const [results] = await connection.query(
            'SELECT title, date, startTime, endTime, location FROM events'
        );
        res.status(200).send(results);
    } catch (error) {
        console.error('Error:', error);
        res.status(500).send({ error: 'Error retrieving events' });
    } finally {
        connection.release();
    }
});

router.get('/getEventById/:eventId', async (req, res) => {
    const { eventId } = req.params;
    const connection = await pool.getConnection();
    try {
        const [results] = await connection.query(
            'SELECT * FROM events WHERE id = ?',
            [eventId]
        );
        res.status(200).send(results);
    } catch (error) {
        console.error('Error:', error);
        res.status(500).send({ error: 'Error retrieving event' });
    } finally {
        connection.release();
    }
});

export default router;
