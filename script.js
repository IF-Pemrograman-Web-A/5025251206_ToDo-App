let todos = [
    {
        id: 1,
        title: "KPPL",
        description: "Tugas mata kuliah KPPL",
        dueDate: "2026-09-20",
        completed: false
    },
    {
        id: 2,
        title: "PBO",
        description: "Tugas Pemrograman Berorientasi Objek",
        dueDate: "2026-09-25",
        completed: false
    },
    {
        id: 3,
        title: "PWEB",
        description: "Tugas E01a membuat website todo list",
        dueDate: "2026-09-27",
        completed: false
    }
];

let editModeId = null; 

const todoListEl = document.getElementById('todo-list');
const todoForm = document.getElementById('todo-form');
const taskDetailCard = document.getElementById('task-detail-card');
const submitBtn = document.getElementById('submit-btn');
const themeToggleBtn = document.getElementById('theme-toggle');


function renderTodos() {
    todoListEl.innerHTML = ''; 

    todos.forEach(todo => {
        const li = document.createElement('li');
        
        const isChecked = todo.completed ? 'checked' : '';
        const textClass = todo.completed ? 'completed-task' : '';

        li.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" onchange="toggleComplete(${todo.id})" ${isChecked}>
                <strong class="${textClass}" style="cursor: pointer; font-size: 1.1em;" onclick="viewDetail(${todo.id})">${todo.title}</strong>
            </div>
            <div style="margin-top: 5px;">
                <small>Due: ${todo.dueDate}</small>
            </div>
            <div class="action-btns">
                <!-- CRITERIA 2: Buttons for edit and delete -->
                <button type="button" onclick="editTodo(${todo.id})">Edit</button>
                <button type="button" onclick="deleteTodo(${todo.id})">Delete</button>
            </div>
        `;
        todoListEl.appendChild(li);
    });
}


todoForm.addEventListener('submit', function(event) {
    event.preventDefault(); 

    const title = document.getElementById('task_name').value;
    const description = document.getElementById('task_description').value;
    const dueDate = document.getElementById('task_deadline').value;

    if (editModeId !== null) {

        const index = todos.findIndex(t => t.id === editModeId);
        todos[index].title = title;
        todos[index].description = description;
        todos[index].dueDate = dueDate;
        
        editModeId = null; 
        submitBtn.textContent = 'Add Task';
    } else {
        const newTodo = {
            id: Date.now(), 
            title: title,
            description: description,
            dueDate: dueDate,
            completed: false
        };
        todos.push(newTodo);
    }

    todoForm.reset(); 
    renderTodos();    
});


function deleteTodo(id) {
    todos = todos.filter(todo => todo.id !== id);
    
    taskDetailCard.innerHTML = `<p><em>Click a task name to view details</em></p>`;
    
    renderTodos();
}

function toggleComplete(id) {
    const todo = todos.find(t => t.id === id);
    todo.completed = !todo.completed;
    renderTodos();
    viewDetail(id); 
}


function editTodo(id) {
    const todo = todos.find(t => t.id === id);
    
    document.getElementById('task_name').value = todo.title;
    document.getElementById('task_description').value = todo.description;
    document.getElementById('task_deadline').value = todo.dueDate;

    editModeId = id; 
    submitBtn.textContent = 'Update Task'; 
}


function viewDetail(id) {
    const todo = todos.find(t => t.id === id);
    const statusText = todo.completed ? 'Completed' : 'In Progress';
    
    taskDetailCard.innerHTML = `
        <h3>${todo.title}</h3>
        <p><strong>Status:</strong> ${statusText}</p>
        <p><strong>Due:</strong> ${todo.dueDate}</p>
        <p>${todo.description}</p>
    `;
}


themeToggleBtn.addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
});


renderTodos(); 