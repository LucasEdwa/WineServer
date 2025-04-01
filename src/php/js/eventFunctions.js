let wineList = [];
let activityList = [];

try {
    wineList = JSON.parse(document.getElementById('wineCollection').value || '[]');
    activityList = JSON.parse(document.getElementById('activities').value || '[]');
    console.log('Wine List:', wineList); // Debugging
    console.log('Activity List:', activityList); // Debugging
} catch (error) {
    console.error('Error parsing wineCollection or activities:', error);
}

// Update the wine list in the DOM
function updateWineList() {
    const wineListElement = document.getElementById('wineList');
    if (!wineListElement) return; // Ensure the element exists
    wineListElement.innerHTML = '';
    wineList.forEach((wine, index) => {
        const listItem = document.createElement('li');
        listItem.textContent = `${wine.name} (${wine.variety}, ${wine.year}, ${wine.region}) - $${wine.price}`;
        const removeButton = document.createElement('button');
        removeButton.textContent = 'Remove';
        removeButton.onclick = () => removeWine(index);
        listItem.appendChild(removeButton);
        wineListElement.appendChild(listItem);
    });
    document.getElementById('wineCollection').value = JSON.stringify(wineList);
}

// Add a new wine to the list
function addWine() {
    const name = document.getElementById('wineName').value;
    const variety = document.getElementById('wineVariety').value;
    const year = document.getElementById('wineYear').value;
    const region = document.getElementById('wineRegion').value;
    const price = document.getElementById('winePrice').value;
    const description = document.getElementById('wineDescription').value;

    if (!name || !variety || !year || !region || !price || !description) {
        alert('Please fill in all wine fields.');
        return;
    }

    const wine = {
        name,
        variety,
        year: parseInt(year),
        region,
        price: parseFloat(price),
        description
    };

    wineList.push(wine);
    updateWineList();

    // Clear input fields
    document.getElementById('wineName').value = '';
    document.getElementById('wineVariety').value = '';
    document.getElementById('wineYear').value = '';
    document.getElementById('wineRegion').value = '';
    document.getElementById('winePrice').value = '';
    document.getElementById('wineDescription').value = '';
}

// Update the activity list in the DOM
function updateActivityList() {
    const activityListElement = document.getElementById('activityList');
    if (!activityListElement) return; // Ensure the element exists
    activityListElement.innerHTML = '';
    activityList.forEach((activity, index) => {
        const materials = activity.materials.join(', ');
        const listItem = document.createElement('li');
        listItem.textContent = `Title: ${activity.title}, Duration: ${activity.duration} mins, Difficulty: ${activity.difficulty}, Materials: ${materials}`;
        const removeButton = document.createElement('button');
        removeButton.textContent = 'Remove';
        removeButton.onclick = () => removeActivity(index);
        listItem.appendChild(removeButton);
        activityListElement.appendChild(listItem);
    });
    document.getElementById('activities').value = JSON.stringify(activityList);
}

// Add a new activity to the list
function addActivity() {
    const title = document.getElementById('activityTitle').value;
    const duration = document.getElementById('activityDuration').value;
    const difficulty = document.getElementById('activityDifficulty').value;
    const materials = document.getElementById('activityMaterials').value.split(',');

    if (!title || !duration || !difficulty || materials.length === 0) {
        alert('Please fill in all activity fields.');
        return;
    }

    const activity = {
        title,
        duration: parseInt(duration),
        difficulty,
        materials
    };

    activityList.push(activity);
    updateActivityList();

    // Clear input fields
    document.getElementById('activityTitle').value = '';
    document.getElementById('activityDuration').value = '';
    document.getElementById('activityDifficulty').value = 'beginner';
    document.getElementById('activityMaterials').value = '';
}

// Remove a wine from the list
function removeWine(index) {
    wineList.splice(index, 1);
    updateWineList();
}

// Remove an activity from the list
function removeActivity(index) {
    activityList.splice(index, 1);
    updateActivityList();
}

// Delete an event
function deleteEvent(id) {
    if (confirm("Are you sure you want to delete this event?")) {
        fetch(`/api/deleteEvent/${id}`, {
            method: "DELETE",
        })
            .then(response => {
                if (response.ok) {
                    window.location.href = "/";
                } else {
                    return response.text().then(error => {
                        throw new Error(error);
                    });
                }
            })
            .catch(error => {
                alert("Failed to delete the event: " + error.message);
            });
    }
}

// Initialize the lists on page load
window.onload = function () {
    updateWineList();
    updateActivityList();
};
