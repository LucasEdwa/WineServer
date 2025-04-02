import pool from './connection';
import dotenv from 'dotenv';
import { TWineCollection, TActivity } from './models/types';


// Define image paths relative to server
const IMAGES = {
    // event 1
    wine1: '/images/image.png',
    wine2: '/images/image2.png',
    wine3: '/images/wine.3.jpg',
    //  event 2
    wine4: '/images/wine.4.jpg',
    wine5: '/images/wine.5.jpg',
    // event 3
    wine6: '/images/wine.6.jpg',
    wine7: '/images/wine.7.jpg',
    // event 4
    wine8: '/images/wine.8.jpg',
    wine9: '/images/wine.9.jpg',
};

const events = [
    {
        title: 'Summer Wine Tasting Extravaganza',
        description: 'Immerse yourself in a sophisticated evening of exquisite summer wines, expertly curated to enhance your seasonal palate. Sip on refreshing whites and elegant reds while enjoying a vibrant ambiance at Downtown Wine Bar. Perfect for wine enthusiasts and casual sippers alike, this event offers a memorable experience of fine flavors, gourmet pairings, and engaging activities.',
        imageUrl: IMAGES.wine3,
        date: '2024-06-15',
        startTime: '18:00',
        endTime: '21:00',
        location: 'Downtown Wine Bar',
        capacity: 20,
        price: 45.00,
        isPrivate: true,
        wineCollection: [
            {
                name: 'Chardonnay Reserve',
                variety: 'White',
                year: 2020,
                region: 'Napa Valley',
                price: 45.00,
                description: 'A luxurious Chardonnay boasting creamy textures, delicate citrus notes, and a beautifully balanced oak finish.',
                imageUrl: `${IMAGES.wine1}`
            },
            {
                name: 'Sonoma Merlot',
                variety: 'Red',
                year: 2019,
                region: 'Sonoma',
                price: 35.00,
                description: 'An enticing Merlot with lush black cherry, velvety tannins, and a smooth, lingering finish.',
                imageUrl: `${IMAGES.wine2}`
            }
        ] as TWineCollection[],
        activities: [
            {
                title: 'Wine and Paint Night',
                duration: 60,
                difficulty: 'beginner',
                materials: ['Canvas', 'Paint', 'Brushes']
            },
            {
                title: 'Artisanal Cheese & Wine Pairing',
                duration: 90,
                difficulty: 'intermediate',
                materials: ['Gourmet Cheese Platter', 'Wine Glasses', 'Tasting Notes']
            }
        ] as unknown as TActivity[],
    },
    {
        title: 'Luxury Reds: A Night of Bold Flavors',
        description: 'An exclusive tasting event dedicated to lovers of bold, full-bodied red wines. Explore a hand-selected collection of vintage reds from top vineyards, paired with premium dark chocolates and aged cheeses for the ultimate sensory experience.',
        imageUrl: IMAGES.wine4,
        date: '2024-07-20',
        startTime: '19:00',
        endTime: '22:00',
        location: 'The Velvet Cellar',
        capacity: 25,
        price: 60.00,
        isPrivate: true,
        wineCollection: [
            {
                name: 'Cabernet Sauvignon Reserve',
                variety: 'Red',
                year: 2018,
                region: 'Bordeaux',
                price: 65.00,
                description: 'A rich and intense wine with layers of blackcurrant, espresso, and a hint of leather.',
                imageUrl: `${IMAGES.wine5}`
            }
        ] as TWineCollection[],
        activities: [
            {
                title: 'Sommelier’s Tasting Challenge',
                duration: 75,
                difficulty: 'advanced',
                materials: ['Blind Tasting Glasses', 'Aged Cheeses', 'Chocolate Pairings']
            }
        ] as unknown as TActivity[],
    },
    {
        title: 'Sparkling Sensations: A Champagne Celebration',
        description: 'Indulge in a glamorous night of bubbles and elegance as we explore the world of sparkling wines. From crisp Proseccos to rich Champagnes, experience effervescence like never before!',
        imageUrl: IMAGES.wine6,
        date: '2024-08-10',
        startTime: '18:30',
        endTime: '21:30',
        location: 'Skyline Lounge',
        capacity: 30,
        price: 75.00,
        isPrivate: true,
        wineCollection: [
            {
                name: 'Brut Champagne',
                variety: 'Sparkling',
                year: 2016,
                region: 'Champagne, France',
                price: 85.00,
                description: 'A refined and crisp Champagne with hints of green apple, toasted brioche, and fine bubbles.',
                imageUrl: `${IMAGES.wine7}`
            }
        ] as TWineCollection[],
        activities: [
            {
                title: 'Bubble & Brunch Tasting',
                duration: 90,
                difficulty: 'beginner',
                materials: ['Brunch Pairings', 'Champagne Flutes', 'Tasting Notes']
            }
        ] as unknown as TActivity[],
    },
    {
        title: 'Rosé All Day: A Sunset Wine Experience',
        description: 'Celebrate the beauty of rosé wines with an unforgettable sunset tasting event by the waterfront. Enjoy crisp and fruity rosés, refreshing appetizers, and live acoustic music for a perfect summer evening.',
        imageUrl: IMAGES.wine8,
        date: '2024-09-05',
        startTime: '17:00',
        endTime: '20:00',
        location: 'Harbor View Terrace',
        capacity: 40,
        price: 50.00,
        isPrivate: true,
        wineCollection: [
            {
                name: 'Provence Rosé',
                variety: 'Rosé',
                year: 2022,
                region: 'Provence, France',
                price: 40.00,
                description: 'A light and fresh rosé with delicate notes of wild strawberries and citrus zest.',
                imageUrl: `${IMAGES.wine9}`
            }
        ] as TWineCollection[],
        activities: [
            {
                title: 'Sunset Rosé Pairing & Live Music',
                duration: 120,
                difficulty: 'beginner', 
                materials: ['Appetizers', 'Rosé Glasses', 'Live Acoustic Set']
            }
        ] as unknown as TActivity[],
    }
];


export const insertEvents = async () => {
    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        for (const event of events) {
            console.log(`Checking if event exists: ${event.title}`);
            const [existingEvent] = await connection.query<any[]>(
                `SELECT id FROM events WHERE title = ? AND date = ? AND location = ?`,
                [event.title, event.date, event.location]
            );

            if (existingEvent && existingEvent.length > 0) {
                console.log(`Event already exists: ${event.title}`);
                continue; // Skip inserting this event
            }

            console.log(`Inserting event: ${event.title}`);
            const [eventResult] = await connection.query(
                `INSERT INTO events (title, description, imageUrl, date, startTime, endTime, location, capacity, price,  isPrivate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
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

            // Insert activities and materials
            for (const activity of event.activities) {
                console.log(`Inserting activity for event: ${event.title}`);
                const [activityResult] = await connection.query(
                    `INSERT INTO activities (eventId, title, duration, difficulty)
                    VALUES (?, ?, ?, ?)`,
                    [
                        eventId,
                        activity.title,
                        activity.duration,
                        activity.difficulty
                    ]
                );

                const activityId = (activityResult as any).insertId;

                for (const material of activity.materials) {
                    console.log(`Inserting material: ${material} for activity in event: ${event.title}`);
                    await connection.query(
                        `INSERT INTO materials (activityId, name)
                        VALUES (?, ?)`,
                        [activityId, material]
                    );
                }
            }
        }

        await connection.commit();
        console.log('Events, wines, activities, and materials inserted successfully.');
    } catch (error) {
        await connection.rollback();
        console.error('Error inserting events:', error);
    } finally {
        connection.release();
    }
};

