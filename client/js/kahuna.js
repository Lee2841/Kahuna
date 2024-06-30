// ChatGPT was used for the Javascript, HTML and CSS as I did not have any previous experience using them.
// It was used for assistance on the backend too but not as much as I had the course notes.

document.addEventListener("DOMContentLoaded", init);
const BASE_URI = 'http://localhost:8000/kahuna/api/';

function init() {
    const currentPath = window.location.pathname;

    if (currentPath.includes("/login.html")) {
        bindLogin();
    } else if (currentPath.includes("/register.html")) {
        bindRegister();
    } else if (currentPath.includes("/home.html")) {
        bindHome();
        bindLogout();
    } else if (currentPath.includes("/myproducts.html")) {
        bindMyProducts();
        bindLogout();
    } else if (currentPath.includes("/tickets.html")) {
        bindTicketDetailsButtons();
        bindUserTickets(); // Fetch and display user tickets
        bindLogout();
    } else if (currentPath.includes("/submit_ticket.html")) {
        bindTicketForm(); // Bind ticket submission form
        bindLogout();
    } else if (currentPath.includes("/ticket_replies.html")) {
        bindReplyForm();
        bindLogout();
    }
}

async function isValidToken(token, user) {
    try {
        const res = await fetch(`${BASE_URI}token`, {
            headers: {
                'X-Api-Key': token,
                'X-Api-User': user
            }
        });
        const data = await res.json();
        return data.data.valid;
    } catch (err) {
        return false;
    }
}

async function fetchDataFromAPI(url, method = 'GET') {
    const token = localStorage.getItem('kahuna_token');
    const user = localStorage.getItem('kahuna_user');

    const validToken = await isValidToken(token, user);
    if (!validToken) {
        showMessage('Token is invalid. Redirecting to login page.', 'danger');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
        return;
    }

    const response = await fetch(url, {
        method,
        headers: {
            'X-Api-Key': token,
            'X-Api-User': user,
        },
    });

    if (!response.ok) {
        throw new Error('Failed to perform request.');
    }

    const data = await response.json();
    return data;
}


function bindLogin() {
    document.getElementById('loginForm').addEventListener('submit', async (evt) => {
        evt.preventDefault();
        const formData = new FormData(document.getElementById('loginForm'));
        try {
            const res = await fetch(`${BASE_URI}user/login`, {
                mode: 'cors',
                method: 'POST',
                body: formData
            });

            if (!res.ok) {
                throw new Error('Invalid email or password. Please try again.');
            }

            const data = await res.json();
            console.log(data);

            localStorage.setItem('kahuna_user', data.data.user);
            localStorage.setItem('kahuna_token', data.data.token);

            showMessage('Login successful!', 'success');
            setTimeout(() => {
                window.location.href = '../home.html';
            }, 2000);

        } catch (err) {
            showMessage(err.message, 'danger');
        }
    });
}


function bindLogout() {
    document.getElementById('logoutButton').addEventListener('click', async () => {
        try {
            const token = localStorage.getItem('kahuna_token');
            const user = localStorage.getItem('kahuna_user');

            if (!token || !user) {
                throw new Error('User not authenticated.');
            }
            const res = await fetch(`${BASE_URI}user/logout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Api-Key': token,
                    'X-Api-User': user
                }
            });
            if (!res.ok) {
                throw new Error('Failed to logout. Please try again.');
            }
            localStorage.removeItem('kahuna_token');
            localStorage.removeItem('kahuna_user');
            window.location.href = 'login.html';
        } catch (err) {
            showMessage(err.message, 'danger');
        }
    });
}

function bindRegister() {
    document.getElementById('registerForm').addEventListener('submit', async (evt) => {
        evt.preventDefault();
        const formData = new FormData(document.getElementById('registerForm'));
        try {
            const res = await fetch(`${BASE_URI}user/register`, {
                method: 'POST',
                body: formData
            });

            if (!res.ok) {
                if (res.status === 409) {
                    showMessage('Email already exists. Please login or use a different email.', 'danger');
                    return;
                } else {
                    throw new Error('Failed to register. Please try again.');
                }
            }
            showMessage('Registration successful!', 'success');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } catch (err) {
            showMessage(err.message, 'danger');
        }
    });
}



function bindHome() {

    document.getElementById('productForm').addEventListener('submit', async (evt) => {
        evt.preventDefault();

        const formData = new FormData(document.getElementById('productForm')); // Use FormData to collect form data
        const serialNumber = formData.get('serialNumber'); // Get the value of the serial number field from FormData

        if (serialNumber.trim() === '') {
            // If the serial number is empty, display an error message
            showMessage('Serial number is required.', 'danger');
            return;
        }
        try {
            const token = localStorage.getItem('kahuna_token');
            const user = localStorage.getItem('kahuna_user');

            // Check token validity before submitting the form
            const validToken = await isValidToken(token, user);

            if (validToken) {
                // Token is valid, proceed with form submission
                const res = await fetch(`${BASE_URI}product/register`, {
                    method: 'POST',
                    headers: {
                        'X-Api-Key': token,
                        'X-Api-User': user
                    },
                    body: formData
                });

                if (!res.ok) {
                    throw new Error('Failed to validate serial number. Please try again.');
                }
                showMessage('Serial number validated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = 'myproducts.html';
                }, 2000);
            } else {
                // Token is invalid, redirect to login
                showMessage('Token is invalid. Redirecting to login page.', 'danger');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            }
        } catch (error) {
            showMessage(error.message, 'danger');
        }
    });
}

async function bindMyProducts() {
    try {
        const token = localStorage.getItem('kahuna_token');
        const user = localStorage.getItem('kahuna_user');

        // Check token validity before fetching products
        const validToken = await isValidToken(token, user);

        if (!validToken) {
            // Token is invalid, redirect to login
            console.log('Token is invalid. Redirecting to login page.');
            showMessage('Token is invalid. Redirecting to login page.', 'danger');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
            return;
        }

        const response = await fetch(`${BASE_URI}user/products`, {
            method: 'GET',
            headers: {
                'X-Api-Key': token,
                'X-Api-User': user
            }
        });

        if (response.ok) {
            const responseData = await response.json();
            const products = responseData.data; // Extract products array from the data property

            const contentContainer = document.getElementById('content');
            if (!contentContainer) {
                return;
            }

            if (products.length === 0) {
                contentContainer.innerHTML = '<p class="text-muted">You have no products registered.</p>';
                return;
            }

            contentContainer.innerHTML = '';

            products.forEach(product => {
                const card = document.createElement('div');
                card.classList.add('col-md-6', 'product-card');
                card.innerHTML = `
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">${product.ProductName}</h5>
                            <button class="btn btn-primary" onclick="viewProductDetails({ ProductName: '${product.ProductName}', SerialNumber: '${product.SerialNumber}', WarrantyPeriod: ${product.WarrantyPeriod}, WarrantyStartDate: '${product.WarrantyStartDate}', PurchaseDate: '${product.PurchaseDate}' })">View Details</button>
                            </div>
                        </div>
                    </div>
                `;
                contentContainer.appendChild(card);
            });

        } else if (response.status === 404) {
            showMessage('You have no products registered.', 'info');
            const noProductsMessage = document.createElement('div');
            noProductsMessage.classList.add('col-12', 'text-center', 'mt-4');
            noProductsMessage.innerHTML = '<p class="text-muted">You have no products registered.</p>';
            const contentContainer = document.getElementById('content');
            contentContainer.appendChild(noProductsMessage);
        } else {
            showMessage('Failed to fetch user products. Please try again.', 'danger');
        }
    } catch (error) {
        showMessage('Error fetching user products. Please try again later.', 'danger');
    }
}

function calculateWarrantyTimeLeft(warrantyStartDate, warrantyPeriod) {
    const startDate = new Date(warrantyStartDate);
    const endDate = new Date(startDate.setFullYear(startDate.getFullYear() + warrantyPeriod));
    const today = new Date();
    const timeLeft = endDate - today;
    const daysLeft = Math.ceil(timeLeft / (1000 * 60 * 60 * 24));
    return daysLeft > 0 ? `${daysLeft} days left` : 'Warranty expired';
}

// Function to view product details in the modal
function viewProductDetails(product) {
    const modalTitle = document.getElementById('productModalTitle');
    const modalBody = document.getElementById('productModalBody');
    // Populate modal with product details
    modalTitle.textContent = product.ProductName;
    modalBody.innerHTML = `
        <p>Serial Number: ${product.SerialNumber}</p>
        <p>Warranty Period: ${product.WarrantyPeriod} years</p>
        <p>Warranty Start Date: ${product.WarrantyStartDate}</p>
        <p>Purchase Date: ${product.PurchaseDate}</p>
        <p>Warranty Time Left: ${calculateWarrantyTimeLeft(product.WarrantyStartDate, product.WarrantyPeriod)}</p>
        <button class="btn btn-primary" onclick="openTicketSubmission('${product.SerialNumber}')">Open Ticket</button>
    `;

    // Show the modal
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    productModal.show();
}

function openTicketSubmission(serialNumber) {
    // Redirect to the ticket submission page with serial number as a query parameter
    window.location.href = `submit_ticket.html?serialNumber=${serialNumber}`;
}

function bindTicketForm() {
    document.getElementById('ticketForm').addEventListener('submit', async (evt) => {
        evt.preventDefault();

        const formData = new FormData(document.getElementById('ticketForm'));
        const title = formData.get('title');
        const productSerialNumber = formData.get('productSerialNumber');
        const issueDescription = formData.get('issueDescription');

        try {
            const token = localStorage.getItem('kahuna_token');
            const user = localStorage.getItem('kahuna_user');

            // Check token validity before submitting the form
            const validToken = await isValidToken(token, user);

            if (validToken) {
                const res = await fetch(`${BASE_URI}ticket`, {
                    method: 'POST',
                    headers: {
                        'X-Api-Key': token,
                        'X-Api-User': user
                    },
                    body: formData
                });

                if (!res.ok) {
                    if (res.status === 400) {
                        throw new Error('Invalid serial number. Please enter a valid one.');
                    } else if (res.status === 401) {
                        throw new Error('Unauthorized. Please log-in again.');
                    } else if (res.status === 403) {
                        throw new Error('Forbidden. Please double-check your serial number.');
                    } else if (res.status === 404) {
                        throw new Error('Serial Number does not exist.');
                    } else if (res.status === 422) {
                        throw new Error('Product warranty expired.');
                    } else if (res.status === 500) {
                        throw new Error('Failed to create ticket: Internal server error');
                    } else {
                        throw new Error('Failed to submit ticket. Please try again.');
                    }
                }
                showMessage('Ticket submitted successfully!', 'success');
                setTimeout(() => {
                    window.location.href = 'tickets.html';
                }, 2000);
            } else {
                // Token is invalid, redirect to login
                showMessage('Token is invalid. Redirecting to login page.', 'danger');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            }
        } catch (error) {
            showMessage(error.message, 'danger');
        }
    });
}

async function displayTicketReplies(ticketId) {
    try {
        const token = localStorage.getItem('kahuna_token');
        const user = localStorage.getItem('kahuna_user');

        // Check token validity before fetching ticket replies
        const validToken = await isValidToken(token, user);

        if (!validToken) {
            showMessage('Token is invalid. Redirecting to login page.', 'danger');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
            return;
        }

        let url = `${BASE_URI}ticket/replies`;
        if (ticketId) {
            url += `?ticket_id=${ticketId}`;
        }

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Api-Key': token,
                'X-Api-User': user
            }
        });

        if (response.ok) {
            const responseData = await response.json();
            const ticketReplies = responseData.data; // Extract ticket replies array from the data property

            // Display ticket replies in the container
            const ticketRepliesContainer = document.getElementById('ticketRepliesContainer');

            if (!ticketRepliesContainer) {
                return;
            }

            if (ticketReplies.length === 0) {
                ticketRepliesContainer.innerHTML = '<p>No replies found for this ticket.</p>';
                return;
            }

            ticketRepliesContainer.innerHTML = '';

            async function fetchUserName(userId) {
                const userResponse = await fetch(`${BASE_URI}user/${userId}`, {
                    method: 'GET',
                    headers: {
                        'X-Api-Key': token,
                        'X-Api-User': user
                    }
                });
                const userData = await userResponse.json();
                const fullName = `${userData.data.name} ${userData.data.surname}`;

                return fullName;
            }

            for (const reply of ticketReplies) {
                const userName = await fetchUserName(reply.user_id);
                const replyElement = document.createElement('div');
                replyElement.classList.add('reply');
                replyElement.innerHTML = `
                    <div class="reply-user">
                    <p><strong>User:</strong> ${userName}</p>
                    <p><strong>Message:</strong> ${reply.reply_message}</p>
                    <hr>
                `;
                ticketRepliesContainer.appendChild(replyElement);
            }
        } else {
            showMessage('Failed to fetch ticket replies. Please try again.', 'danger');
        }
    } catch (error) {
        showMessage('Error displaying ticket details and replies. Please try again later.', 'danger');
    }
}


function bindTicketDetailsButtons() {
    const buttons = document.querySelectorAll('.ticket-card .btn-primary');
    buttons.forEach(button => {
        button.addEventListener('click', () => {
        });
    });
}

async function bindUserTickets() {
    try {
        const token = localStorage.getItem('kahuna_token');
        const user = localStorage.getItem('kahuna_user');

        // Check token validity before fetching tickets
        const validToken = await isValidToken(token, user);

        if (!validToken) {
            showMessage('Token is invalid. Redirecting to login page.', 'danger');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
            return;
        }

        const response = await fetch(`${BASE_URI}user/tickets`, {
            method: 'GET',
            headers: {
                'X-Api-Key': token,
                'X-Api-User': user
            }
        });

        const responseData = await response.json();
        const tickets = responseData.data; // Extract tickets array from the data property

        const ticketsContainer = document.getElementById('currentTickets');
        if (!ticketsContainer) {
            console.error('Tickets container element not found in the DOM.');
            return;
        }

        if (tickets.length === 0) {
            ticketsContainer.innerHTML = '<p class="text-muted">You have no tickets opened.</p>';
            return;
        }

        tickets.forEach(ticket => {
            const card = document.createElement('div');
            card.classList.add('col-md-6', 'ticket-card');
            card.innerHTML = `
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">${ticket.title}</h5>
                        <p class="card-text">Description: ${ticket.issue_description}</p>
                        <p class="card-text">Status: ${ticket.status}</p>
                    </div>
                    <div class="card-footer">
                        <a href="ticket_replies.html?ticket_id=${ticket.id}&title=${encodeURIComponent(ticket.title)}&description=${encodeURIComponent(ticket.issue_description)}" class="btn btn-primary" data-ticket-id="${ticket.id}">View Details</a>
                    </div>
                </div>
            `;
            ticketsContainer.appendChild(card);
        });

        // Attach event listener to "View Details" buttons
        const buttons = ticketsContainer.querySelectorAll('.ticket-card .btn-primary');
        buttons.forEach(button => {
            button.addEventListener('click', function (event) {
                const ticketId = this.getAttribute('data-ticket-id');
                displayTicketReplies(ticketId);
            });
        });

    } catch (error) {
        console.error('Error fetching user tickets:', error);
        showMessage('Error fetching user tickets. Please try again later.', 'danger');
    }
}

function bindReplyForm() {
    const replyForm = document.getElementById('replyForm');
    replyForm.addEventListener('submit', async (evt) => {
        evt.preventDefault();

        const replyContent = document.getElementById('replyContent').value;
        const ticketId = window.currentTicketId;
        const userId = localStorage.getItem('kahuna_user');

        if (replyContent.trim() === '') {
            showMessage('Reply content cannot be empty.', 'danger');
            return;
        }

        try {
            await submitReply(ticketId, userId, replyContent);
            displayTicketReplies(ticketId); // Reload replies after submission
            replyForm.reset();
        } catch (error) {
            console.error('Error submitting reply:', error);
            showMessage('Failed to submit reply', 'danger');
        }
    });
}

async function submitReply(ticket_id, user_id, reply_message) {
    const token = localStorage.getItem('kahuna_token');
    const user = localStorage.getItem('kahuna_user');

    const validToken = await isValidToken(token, user);

    if (validToken) {
        try {
            const formData = new FormData();
            formData.append('ticketId', ticket_id);
            formData.append('userId', user_id);
            formData.append('replyMessage', reply_message);

            const response = await fetch(`${BASE_URI}ticket/reply`, {
                method: 'POST',
                headers: {
                    'X-Api-Key': token,
                    'X-Api-User': user
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Failed to submit reply');
            }

        } catch (error) {
            showMessage('Failed to submit reply', 'danger');
        }
    } else {
        // Token is invalid, redirect to login
        console.log('Token is invalid. Redirecting to login page.');
        showMessage('Token is invalid. Redirecting to login page.', 'danger');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    }
}

function showMessage(message, type = 'danger') {
    const alertElement = document.getElementById('error-message');
    alertElement.textContent = message;
    alertElement.className = `alert alert-${type}`;
    alertElement.style.display = 'block';
    setTimeout(() => {
        alertElement.style.display = 'none';
    }, 3000);
}