const todoListEl = document.getElementById('todo-list');
const todoForm = document.getElementById('todo-form');
const taskDetailCard = document.getElementById('task-detail-card');
const submitBtn = document.getElementById('submit-btn');
const themeToggleBtn = document.getElementById('theme-toggle');
const announcer = document.getElementById('a11y-announcer');

let db;
let todos = []; 
let editModeId = null;

// LOCAL STORAGE (User Preferences)

function initTheme() {
    const savedTheme = localStorage.getItem('themePreference');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
}
initTheme();

themeToggleBtn.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('themePreference', isDark ? 'dark' : 'light'); // Save to LocalStorage
    announce(isDark ? "Dark mode enabled" : "Light mode enabled");
});

// SERVICE WORKER REGISTRATION & NOTIFICATION

if ('serviceWorker' in navigator && 'Notification' in window) {
    navigator.serviceWorker.register('sw.js')
        .then(() => console.log('Service Worker Registered'))
        .catch(err => console.error('Service Worker Error', err));

    // Minta izin notifikasi saat web dimuat
    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
}

// Fungsi Mengecek Waktu Notifikasi (Berjalan setiap 10 detik)
setInterval(() => {
    const now = new Date().getTime();
    todos.forEach(todo => {
        if (todo.notifTime && !todo.completed && !todo.notified) {
            const timeToNotify = new Date(todo.notifTime).getTime();
            if (now >= timeToNotify) {
                // Tampilkan Notifikasi via Service Worker
                navigator.serviceWorker.ready.then(reg => {
                    reg.showNotification("Todo Reminder: " + todo.title, {
                        body: todo.description,
                        icon: 'https://cdn-icons-png.flaticon.com/512/1055/1055685.png' // Icon dummy
                    });
                });
                // Tandai sudah dinotifikasi dan update DB
                todo.notified = true;
                updateTodoInDB(todo);
            }
        }
    });
}, 10000); 

// INDEXEDDB (App Data)

const request = indexedDB.open('TodoDatabase', 1);

request.onupgradeneeded = function(event) {
    db = event.target.result;
    if (!db.objectStoreNames.contains('todosStore')) {
        db.createObjectStore('todosStore', { keyPath: 'id' });
    }
};

request.onsuccess = function(event) {
    db = event.target.result;
    loadTodos(); // Load data saat web pertama dibuka
};

function loadTodos() {
    const transaction = db.transaction(['todosStore'], 'readonly');
    const store = transaction.objectStore('todosStore');
    const getRequest = store.getAll();

    getRequest.onsuccess = function() {
        todos = getRequest.result;
        renderTodos();
    };
}

function updateTodoInDB(todoObj) {
    const transaction = db.transaction(['todosStore'], 'readwrite');
    const store = transaction.objectStore('todosStore');
    store.put(todoObj);
    loadTodos();
}

function deleteTodoFromDB(id) {
    const transaction = db.transaction(['todosStore'], 'readwrite');
    const store = transaction.objectStore('todosStore');
    store.delete(id);
    loadTodos();
}


function renderTodos() {
    todoListEl.innerHTML = '';
    todos.forEach(todo => {
        const li = document.createElement('li');
        const isChecked = todo.completed ? 'checked' : '';
        const textClass = todo.completed ? 'completed-task' : '';

        // Aksesibilitas (aria-labels, tabindex)
        li.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" aria-label="Mark ${todo.title} as complete" onchange="toggleComplete(${todo.id})" ${isChecked}>
                <strong class="${textClass}" style="cursor: pointer; font-size: 1.1em;" onclick="viewDetail(${todo.id})" tabindex="0" onkeypress="if(event.key==='Enter') viewDetail(${todo.id})">${todo.title}</strong>
            </div>
            <div style="margin-top: 5px;"><small>Due: ${todo.dueDate}</small></div>
            <div class="action-btns">
                <button type="button" aria-label="Edit task ${todo.title}" onclick="editTodo(${todo.id})">Edit</button>
                <button type="button" aria-label="Delete task ${todo.title}" onclick="deleteTodo(${todo.id})">Delete</button>
            </div>
        `;
        todoListEl.appendChild(li);
    });
}


todoForm.addEventListener('submit', async function(event) {
    event.preventDefault();

    const title = document.getElementById('task_name').value;
    const description = document.getElementById('task_description').value;
    const dueDate = document.getElementById('task_deadline').value;
    const notifTime = document.getElementById('task_notif').value;
    const imageInput = document.getElementById('task_image');
    
    let base64Image = null;

    //  Media Capture API (Convert file to Base64)
    if (imageInput.files && imageInput.files[0]) {
        base64Image = await getBase64(imageInput.files[0]);
    }

    if (editModeId !== null) {
        // Mode Edit
        const todoToUpdate = todos.find(t => t.id === editModeId);
        todoToUpdate.title = title;
        todoToUpdate.description = description;
        todoToUpdate.dueDate = dueDate;
        todoToUpdate.notifTime = notifTime;
        if(base64Image) todoToUpdate.image = base64Image; 
        todoToUpdate.notified = false; // Reset notif

        updateTodoInDB(todoToUpdate);
        editModeId = null;
        submitBtn.textContent = 'Add Task';
        announce('Task updated successfully');
    } else {
        // Mode Tambah
        const newTodo = {
            id: Date.now(),
            title: title,
            description: description,
            dueDate: dueDate,
            notifTime: notifTime,
            image: base64Image,
            completed: false,
            notified: false
        };
        updateTodoInDB(newTodo);
        announce('New task added successfully');
    }
    todoForm.reset();
});

function toggleComplete(id) {
    const todo = todos.find(t => t.id === id);
    todo.completed = !todo.completed;
    updateTodoInDB(todo);
    viewDetail(id);
    announce(`Task ${todo.title} is now ${todo.completed ? 'completed' : 'incomplete'}`);
}

function deleteTodo(id) {
    deleteTodoFromDB(id);
    taskDetailCard.innerHTML = `<p><em>Click a task name to view details</em></p>`;
    announce('Task deleted');
}

function editTodo(id) {
    const todo = todos.find(t => t.id === id);
    document.getElementById('task_name').value = todo.title;
    document.getElementById('task_description').value = todo.description;
    document.getElementById('task_deadline').value = todo.dueDate;
    document.getElementById('task_notif').value = todo.notifTime || '';

    editModeId = id;
    submitBtn.textContent = 'Update Task';
    document.getElementById('task_name').focus(); 
}

function viewDetail(id) {
    const todo = todos.find(t => t.id === id);
    const statusText = todo.completed ? 'Completed' : 'In Progress';
    
    let html = `
        <h3>${todo.title}</h3>
        <p><strong>Status:</strong> ${statusText}</p>
        <p><strong>Due:</strong> ${todo.dueDate}</p>
        <p><strong>Notification:</strong> ${todo.notifTime ? todo.notifTime.replace('T', ' ') : 'None'}</p>
        <p>${todo.description}</p>
    `;

    if (todo.image) {
        html += `<img src="${todo.image}" alt="Attachment for ${todo.title}" class="todo-image-preview">`;
    }

    taskDetailCard.innerHTML = html;
    announce(`Viewing details for ${todo.title}`);
}

// Convert image to Base64 (Untuk disimpan di IndexedDB)
function getBase64(file) {
   return new Promise((resolve, reject) => {
     const reader = new FileReader();
     reader.readAsDataURL(file);
     reader.onload = () => resolve(reader.result);
     reader.onerror = error => reject(error);
   });
}

function announce(message) {
    announcer.textContent = message;
    setTimeout(() => { announcer.textContent = ''; }, 3000); 
}