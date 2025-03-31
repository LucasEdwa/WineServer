import fs from 'fs';
import path from 'path';
import pool from '../connection';

async function cleanupImages() {
    const eventImagesDir = path.join(__dirname, '../images');
    const wineImagesDir = path.join(__dirname, '../images/wineimages');

    const connection = await pool.getConnection();
    try {
        // Step 1: Get all image URLs from the database
        const [eventImagesRows] = await connection.query('SELECT imageUrl FROM events') as [{ imageUrl: string }[], any];
        const [wineImagesRows] = await connection.query('SELECT imageUrl FROM wineCollection') as [{ imageUrl: string }[], any];

        const usedImages = new Set<string>();
        eventImagesRows.forEach((row) => usedImages.add(row.imageUrl));
        wineImagesRows.forEach((row) => usedImages.add(row.imageUrl));

        // Step 2: List all files in the event images directory
        const eventFiles = fs.readdirSync(eventImagesDir);
        eventFiles.forEach(file => {
            const filePath = path.join(eventImagesDir, file);
            if (fs.statSync(filePath).isFile()) { // Ensure it's a file
                const relativePath = `/images/${file}`;
                if (!usedImages.has(relativePath)) {
                    // Delete unused event image
                    fs.unlinkSync(filePath);
                    console.log(`Deleted unused event image: ${file}`);
                }
            }
        });

        // Step 3: List all files in the wine images directory
        const wineFiles = fs.readdirSync(wineImagesDir);
        wineFiles.forEach(file => {
            const filePath = path.join(wineImagesDir, file);
            if (fs.statSync(filePath).isFile()) { // Ensure it's a file
                const relativePath = `/images/wineimages/${file}`;
                if (!usedImages.has(relativePath)) {
                    // Delete unused wine image
                    fs.unlinkSync(filePath);
                    console.log(`Deleted unused wine image: ${file}`);
                }
            }
        });

        console.log('Cleanup completed successfully.');
    } catch (error) {
        console.error('Error during cleanup:', error);
    } finally {
        connection.release();
    }
}

cleanupImages();
