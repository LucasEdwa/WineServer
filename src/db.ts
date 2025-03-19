import pool from './connection';
import dotenv from 'dotenv';

dotenv.config();

const SERVER_URL = process.env.SERVER_URL || 'http://localhost:3000';

// Define image paths relative to server
const IMAGES = {
    wine1: '/images/wine.1.jpg',
    wine2: '/images/wine.2.jpg',
    wine3: '/images/wine.3.jpg',
    wine4: '/images/wine.4.jpg'
};

const events = [
    {
        title: 'Summer Wine Tasting',
        description: 'Discover amazing summer wines',
        imageUrl: IMAGES.wine3,  
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
    }
];

export const insertEvents = async () => {
    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        for (const event of events) {
            console.log(`Inserting event: ${event.title}`);
            await connection.query(
                `INSERT INTO events (title, description, imageUrl, date, startTime, endTime, location, capacity, price, currentAttendees, wineSelection, activities, isPrivate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                    event.title,
                    event.description,
                    event.imageUrl,  // Use the image path directly
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

