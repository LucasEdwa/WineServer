import pool from './connection';
import dotenv from 'dotenv';
import { TWineCollection, TActivity } from './models/types';

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
        isPrivate: false,
        wineCollection: [
            {
                name: 'Chardonnay',
                variety: 'White',
                year: 2020,
                region: 'Napa Valley',
                price: 45.00,
                description: 'A rich and buttery wine with notes of vanilla and oak.',
                imageUrl: `${SERVER_URL}${IMAGES.wine1}`
            },
            {
                name: 'Merlot',
                variety: 'Red',
                year: 2019,
                region: 'Sonoma',
                price: 35.00,
                description: 'A medium-bodied wine with flavors of black cherry and plum.',
                imageUrl: `${SERVER_URL}${IMAGES.wine2}`
            }
        ] as TWineCollection[],
        activities: [
            {
                duration: 60,
                difficulty: 'beginner',
                materials: ['Canvas', 'Paint', 'Brushes']
            },
            {
                duration: 90,
                difficulty: 'intermediate',
                materials: ['Wine Glass', 'Notebook']
            }
        ] as TActivity[]
    }
];

export const insertEvents = async () => {
    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        for (const event of events) {
            // Insert event
            console.log(`Inserting event: ${event.title}`);
            const [eventResult] = await connection.query(
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
                    JSON.stringify([]), // Placeholder for wineSelection
                    JSON.stringify([]), // Placeholder for activities
                    event.isPrivate
                ]
            );

            const eventId = (eventResult as any).insertId;

            // Insert wineCollection
            for (const wine of event.wineCollection) {
                console.log(`Inserting wine: ${wine.name} for event: ${event.title}`);
                await connection.query(
                    `INSERT INTO wineCollection (eventId, name, variety, year, region, price, description, imageUrl)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
                    [
                        eventId,
                        wine.name,
                        wine.variety,
                        wine.year,
                        wine.region,
                        wine.price,
                        wine.description,
                        wine.imageUrl
                    ]
                );
            }

            // Insert activities
            for (const activity of event.activities) {
                console.log(`Inserting activity for event: ${event.title}`);
                await connection.query(
                    `INSERT INTO activities (eventId, duration, difficulty, materials)
                    VALUES (?, ?, ?, ?)`,
                    [
                        eventId,
                        activity.duration,
                        activity.difficulty,
                        JSON.stringify(activity.materials)
                    ]
                );
            }
        }

        await connection.commit();
        console.log('Events, wines, and activities inserted successfully.');
    } catch (error) {
        await connection.rollback();
        console.error('Error inserting events:', error);
    } finally {
        connection.release();
    }
};

