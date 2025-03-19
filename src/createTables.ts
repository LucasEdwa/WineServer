import pool from "./connection";

export default async function createTables() {
    const connection = await pool.getConnection();
    try {
        // Drop tables if they exist
        await connection.query(`DROP TABLE IF EXISTS Bookings`);
        await connection.query(`DROP TABLE IF EXISTS events`);
        await connection.query(`DROP TABLE IF EXISTS users`);

        // Create tables with new schema
        await connection.query(`CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            firstName VARCHAR(255) NOT NULL,
            lastName VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL
        )`);

        // Events table remains the same
        await connection.query(`CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            imageUrl VARCHAR(255),
            date DATE,
            startTime TIME,
            endTime TIME,
            location VARCHAR(255),
            capacity INT,
            price DECIMAL(10, 2),
            currentAttendees INT,
            wineSelection JSON,
            activities JSON,
            isPrivate BOOLEAN
        )`);

        // Update Bookings table
        await connection.query(`CREATE TABLE IF NOT EXISTS Bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            userId INT NOT NULL,
            eventId INT NOT NULL,
            eventTitle VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            FOREIGN KEY (userId) REFERENCES users(id),
            FOREIGN KEY (eventId) REFERENCES events(id)
        )`);

        console.log('Tables created successfully.');
    } catch (error) {
        console.error('Error creating tables:', error);
        throw error;
    } finally {
        connection.release();
    }
}