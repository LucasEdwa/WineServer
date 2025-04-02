import pool from "./connection";

export default async function createTables() {
    const connection = await pool.getConnection();
    try {
        // Drop dependent tables first to avoid foreign key constraint errors
        await connection.query(`DROP TABLE IF EXISTS materials`);
        await connection.query(`DROP TABLE IF EXISTS activities`);
        await connection.query(`DROP TABLE IF EXISTS wineCollection`);
        await connection.query(`DROP TABLE IF EXISTS Bookings`);
        await connection.query(`DROP TABLE IF EXISTS events`);
        await connection.query(`DROP TABLE IF EXISTS users`);


        // Create tables with optimized schema
        await connection.query(`CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            firstName VARCHAR(255) NOT NULL,
            lastName VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL
        )`);

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
            isPrivate BOOLEAN
        )`);

        await connection.query(`CREATE TABLE IF NOT EXISTS Bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            userId INT NOT NULL,
            eventId INT NOT NULL,
            eventTitle VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            FOREIGN KEY (userId) REFERENCES users(id),
            FOREIGN KEY (eventId) REFERENCES events(id)
        )`);

        await connection.query(`CREATE TABLE IF NOT EXISTS wineCollection (
            id INT AUTO_INCREMENT PRIMARY KEY,
            eventId INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            variety VARCHAR(255),
            year INT,
            region VARCHAR(255),
            price DECIMAL(10, 2),
            description TEXT,
            imageUrl VARCHAR(255),
            FOREIGN KEY (eventId) REFERENCES events(id) ON DELETE CASCADE
        )`);

        await connection.query(`CREATE TABLE IF NOT EXISTS activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            eventId INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            duration INT NOT NULL,
            difficulty ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
            FOREIGN KEY (eventId) REFERENCES events(id) ON DELETE CASCADE
        )`);

        await connection.query(`CREATE TABLE IF NOT EXISTS materials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            activityId INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            FOREIGN KEY (activityId) REFERENCES activities(id) ON DELETE CASCADE
        )`);

        console.log('Tables created successfully.');
    } catch (error) {
        console.error('Error creating tables:', error);
        throw error;
    } finally {
        connection.release();
    }
}