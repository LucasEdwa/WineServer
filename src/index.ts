import express from 'express';
import createTables from './createTables';
import pool from './connection';
import Router from './routes/router';
import { insertEvents } from './db';
import cors from 'cors';
import path from 'path';

const app = express();

app.use(cors({
    origin: 'http://localhost:5173', // Specify the frontend origin
    methods: ['GET', 'POST', 'PUT', 'DELETE'],
    allowedHeaders: ['Content-Type', 'Authorization'],
    credentials: true // Allow credentials
}));

app.use(express.json());
app.use('/api', Router);
app.use('/images', express.static(path.join(__dirname, '../public/images')));

pool.getConnection()
    .then(connection => {
        console.log('Connected to the MySQL server.');
        connection.release();
        createTables().then(() => {
            insertEvents(); // Ensure this is called after tables are created
        });
    })
    .catch(err => {
        console.error('Error connecting to the MySQL server:', err);
    });


const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`Server is running on port ${port}!`));