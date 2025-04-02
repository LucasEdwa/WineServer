export type TUser = {
    id: number;
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
};

export type TEvent = {
    id: number;
    title: string;
    description: string;
    imageUrl: string;   
    date: string;
    startTime: string;
    endTime: string;
    location: string;
    capacity: number;
    price: number;
    wineCollection: TWineCollection[]; // Updated from wineSelection to wineCollection
    activities: TActivity[];
    isPrivate: boolean;
    isActive: boolean;
};

export type TWineCollection = {
    id?: number; // Optional for new entries
    eventId: number;
    name: string;
    variety: string;
    year: number;
    region: string;
    price: number;
    description: string;
    imageUrl: string;
};

export type TActivity = {
    id?: number; // Optional for new entries
    eventId: number;
    title: string; // Added title field
    duration: number;
    difficulty: 'beginner' | 'intermediate' | 'advanced';
    materials: TMaterial[];
};

export type TMaterial = {
    id?: number; // Optional for new entries
    activityId: number;
    name: string;
  
};

export type TBooking = {
    id: number;
    userId: number;
    eventId: number;
    eventTitle: string;
    date: string;
};

export type Error = {
    error: string;
};