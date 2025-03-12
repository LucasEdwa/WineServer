import pool from './connection';
import dotenv from 'dotenv';
dotenv.config();

const SERVER_URL = process.env.SERVER_URL || 'http://localhost:3000';

const events = [
    {
        title: 'Summer Wine Tasting',
        description: 'Discover amazing summer wines',
        imageUrl: `${SERVER_URL}/images/wine.3.jpg`,
        date: '2024-06-15',
        startTime: '18:00:00',
        endTime: '21:00:00',
        location: 'Downtown Wine Bar',
        capacity: 20,
        price: 45.00,
        currentAttendees: 0,
        wineSelection: JSON.stringify([]),
        activities: JSON.stringify([]),
        isPrivate: false,
    },
    {
        title: 'Art & Wine Workshop',
        description: 'Create your own art piece while enjoying wine',
        imageUrl: `${SERVER_URL}/images/wine.1.jpg`,
        date: '2024-07-20',
        startTime: '18:00:00',
        endTime: '21:00:00',
        location: 'Downtown Wine Bar',
        capacity: 20,
        price: 45.00,
        currentAttendees: 0,
        wineSelection: JSON.stringify([]),
        activities: JSON.stringify([]),
        isPrivate: false,
    },
    {
        title: 'Book Club & Wine',
        description: 'Join our book club and enjoy wine',
        imageUrl: `${SERVER_URL}/images/wine.4.jpg`,
        date: '2024-08-15',
        startTime: '18:00:00',
        endTime: '21:00:00',
        location: 'Downtown Wine Bar',
        capacity: 20,
        price: 45.00,
        currentAttendees: 0,
        wineSelection: JSON.stringify([]),
        activities: JSON.stringify([]),
        isPrivate: false,
    }
];

export const insertEvents = async () => {
    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        for (const event of events) {
            console.log(`Inserting event: ${event.title}`); // Add logging
            await connection.query(
                `INSERT INTO events (title, description, imageUrl, date, startTime, endTime, location, capacity, price, currentAttendees, wineSelection, activities, isPrivate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                    event.title,
                    event.description,
                    event.imageUrl,
                    event.date,
                    event.startTime,
                    event.endTime,
                    event.location,
                    event.capacity,
                    event.price,
                    event.currentAttendees,
                    event.wineSelection,
                    event.activities,
                    event.isPrivate
                ]
            );
        }

        await connection.commit();
        console.log('Events inserted successfully.');
    } catch (error) {
        await connection.rollback();
        console.error('Error inserting events:', error);
    } finally {
        connection.release();
    }
};

