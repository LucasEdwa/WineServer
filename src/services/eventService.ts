import { UploadedFile } from 'express-fileupload';
import path from 'path';
import pool from '../connection';

export async function createEvent(image: UploadedFile, eventData: {
    title: string;
    description: string;
    date: string;
    startTime: string;
    endTime: string;
    location: string;
    capacity: number;
    price: number;
    isPrivate: boolean;
}) {
    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        // Save image to wineimages folder
        const uploadPath = path.join(__dirname, '../../images/wineimages/', image.name);
        const imageUrl = `/images/wineimages/${image.name}`;
        await image.mv(uploadPath);

        // Insert event
        const [result] = await connection.query(
            `INSERT INTO events (
                title, description, imageUrl, date, 
                startTime, endTime, location, capacity, 
                price, currentAttendees, wineSelection, 
                activities, isPrivate
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [
                eventData.title,
                eventData.description,
                imageUrl,
                eventData.date,
                eventData.startTime,
                eventData.endTime,
                eventData.location,
                Number(eventData.capacity),
                Number(eventData.price),
                0,
                JSON.stringify([]),
                JSON.stringify([]),
                Boolean(eventData.isPrivate)
            ]
        );

        await connection.commit();
        return (result as any).insertId;
    } catch (error) {
        await connection.rollback();
        throw error;
    } finally {
        connection.release();
    }
} 

export async function editEvent(eventId: number, eventData: any) {
    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        // Handle image update if provided
        let imageUrl = eventData.currentImageUrl;
        if (eventData.image) {
            const image = eventData.image as UploadedFile;
            const uploadPath = path.join(__dirname, '../../images/wineimages/', image.name);
            await image.mv(uploadPath);
            imageUrl = `/images/wineimages/${image.name}`;
            
            console.log('Image uploaded to:', uploadPath);
        }

        // Update event
        await connection.query(
            `UPDATE events SET 
                title = ?, description = ?, imageUrl = ?,
                date = ?, startTime = ?, endTime = ?, 
                location = ?, capacity = ?, price = ?, 
                isPrivate = ? 
                WHERE id = ?`,
            [
                eventData.title,
                eventData.description,
                imageUrl,
                eventData.date,
                eventData.startTime,
                eventData.endTime,
                eventData.location,
                Number(eventData.capacity),
                Number(eventData.price),
                Boolean(eventData.isPrivate),
                eventId
            ]
        );

        await connection.commit();
        return eventId;
    } catch (error) {
        await connection.rollback();
        console.error('Error in editEvent:', error);
        throw error;
    } finally {
        connection.release();
    }
}
export async function deleteEvent(eventId: number) {
    const connection = await pool.getConnection();
    try {
        await connection.query('DELETE FROM events WHERE id = ?', [eventId]);
    } finally {
        connection.release();
    }
}