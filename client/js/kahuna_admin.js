// ChatGPT was used for the Javascript and HTML as I did not have any previous experience using them especially in the beginning of the project. 
// CSS was pretty much all ChatGPT.
// ChatGPT was used for assistance on the backend too but not as much as I had the course notes.

document.addEventListener("DOMContentLoaded", initAdmin);
const BASE_URI = 'http://localhost:8000/kahuna/api/';

function initAdmin() {
    const currentPath = window.location.pathname;

    if (currentPath.includes("/admin_login.html")) {
        bindLogin();
    } else if (currentPath.includes("/admin_dashboard.html")) {
        bindLogout();
    } else if (currentPath.includes("/admin_products.html")) {
        bindProducts();
        bindProductForm();
        bindLogout();
    } else if (currentPath.includes("/admin_tickets.html")) {
        bindTickets();
        bindLogout();
    }
    else if (currentPath.includes("/admin_ticket_replies.html")) {
        bindReplyForm();
        bindStatusUpdateForm();
        bindLogout();
    }
    else if (currentPath.includes("/admin_users.html")) {
        bindUsers();
        bindUserForm();
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
async function bindLogin() {
    document.getElementById('loginForm').addEventListener('submit', async (evt) => {
        evt.preventDefault();
        const formData = new FormData(document.getElementById('loginForm'));
        try {
            const res = await fetch(`${BASE_URI}admin/login`, {
                mode: 'cors',
                method: 'POST',
                body: formData
            });
            if (!res.ok) {
                throw new Error('Invalid email or password. Please try again.');
            }
            const data = await res.json();

            localStorage.setItem('kahuna_admin_user', data.data.user);
            localStorage.setItem('kahuna_admin_token', data.data.token);

            showMessage('Login successful!', 'success');
            setTimeout(() => {
                window.location.href = '../admin_dashboard.html';
            }, 2000);
        } catch (err) {
            showMessage(err.message, 'danger');
        }
    });
}

async function bindLogout() {
    document.getElementById('logoutButton').addEventListener('click', async () => {
        try {
            const token = localStorage.getItem('kahuna_admin_token');
            const user = localStorage.getItem('kahuna_admin_user');
            if (!token || !user) {
                throw new Error('User not authenticated.');
            }
            const formData = new FormData();
            formData.append('user', user);

            const res = await fetch(`${BASE_URI}admin/logout`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Api-Key': token,
                    'X-Api-User': user
                }
            });
            if (!res.ok) {
                throw new Error('Failed to logout. Please try again.');
            }
            localStorage.removeItem('kahuna_admin_token');
            localStorage.removeItem('kahuna_admin_user');
            window.location.href = 'admin_login.html';
        } catch (err) {
            showMessage(err.message, 'danger');
        }
    });
}
async function bindProducts() {
    try {
        const token = localStorage.getItem('kahuna_admin_token');
        const user = localStorage.getItem('kahuna_admin_user');

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

        const response = await fetch(`${BASE_URI}admin/products`, {
            method: 'GET',
            headers: {
                'X-Api-Key': token,
                'X-Api-User': user
            }
        });

        if (response.ok) {
            const responseData = await response.json();
            console.log(responseData.data);
            const products = responseData.data.products; // Extract products array from the data property
            console.log(products);
            const contentContainer = document.getElementById('content');
            if (!contentContainer) {
                console.error('Content container element not found in the DOM.');
                return;
            }

            if (products.length === 0) {
                contentContainer.innerHTML = '<p class="text-muted">You have no products registered.</p>';
                return;
            }

            contentContainer.innerHTML = ''; // Clear previous content

            products.forEach(product => {
                const card = document.createElement('div');
                card.classList.add('col-md-6', 'product-card');
                card.innerHTML = `
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">${product.ProductName}</h5>
                            <button class="btn btn-primary view-product-button" data-product-id="${product.SerialNumber}">View Details</button>
                            <button class="btn btn-danger delete-product-button" data-product-id="${product.SerialNumber}">Delete Product</button>
                        </div>
                    </div>
                `;
                contentContainer.appendChild(card);
            });

            // Add event listeners for view product buttons
            document.querySelectorAll('.view-product-button').forEach(button => {
                button.addEventListener('click', () => {
                    const productId = button.getAttribute('data-product-id');
                    const product = products.find(p => p.SerialNumber === productId);
                    viewProductDetails(product);
                });
            });

            document.querySelectorAll('.delete-product-button').forEach(button => {
                button.addEventListener('click', () => {
                    const serialNumber = button.getAttribute('data-product-id');
                    deleteProduct(serialNumber);
                });
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

async function deleteProduct(serialNumber) {
    try {
        const token = localStorage.getItem('kahuna_admin_token');
        const user = localStorage.getItem('kahuna_admin_user');

        const validToken = await isValidToken(token, user);

        if (!validToken) {
            showMessage('Token is invalid. Redirecting to login page.', 'danger');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
            return;
        }

        const formData = new FormData();
        formData.append('serialNumber', serialNumber);

        const response = await fetch(`${BASE_URI}admin/product/delete`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Api-Key': token,
                'X-Api-User': user
            }
        });

        if (response.ok) {
            showMessage('Product deleted successfully.', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showMessage('Failed to delete product. Please check if the products warranty started.', 'danger');
        }
    } catch (error) {
        showMessage('Error deleting product. Please try again later.', 'danger');
    }
}

function calculateWarrantyTimeLeft(warrantyStartDate, warrantyPeriod) {

    const startDate = new Date(warrantyStartDate);
    const endDate = new Date(startDate.setFullYear(startDate.getFullYear() + warrantyPeriod));
    const today = new Date();

    if (endDate < today) {
        return 'Warranty expired';
    }

    const timeLeft = endDate - today;
    const daysLeft = Math.ceil(timeLeft / (1000 * 60 * 60 * 24));

    return `${daysLeft} days left`;
}


// Function to view product details in the modal
function viewProductDetails(product) {
    const modalTitle = document.getElementById('productModalTitle');
    const modalBody = document.getElementById('productDetails');
    console.log(product);

    // Determine the warranty and purchase date text
    const warrantyStartDate = product.WarrantyStartDate ? product.WarrantyStartDate : "Not yet registered";
    const purchaseDate = product.PurchaseDate ? product.PurchaseDate : "Not yet registered";

    // Calculate warranty time left only if dates are provided
    const warrantyTimeLeft = product.WarrantyStartDate && product.WarrantyPeriod
        ? calculateWarrantyTimeLeft(product.WarrantyStartDate, product.WarrantyPeriod)
        : "N/A";

    // Populate modal with product details
    modalTitle.textContent = product.ProductName;
    modalBody.innerHTML = `
        <p>Serial Number: ${product.SerialNumber}</p>
        <p>Warranty Period: ${product.WarrantyPeriod} years</p>
        <p>Warranty Start Date: ${warrantyStartDate}</p>
        <p>Purchase Date: ${purchaseDate}</p>
        <p>Warranty Time Left: ${warrantyTimeLeft}</p>
    `;

    // Show the modal
    const productModal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
    productModal.show();
}


function bindProductForm() {
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', async (evt) => {
            evt.preventDefault();

            try {
                const token = localStorage.getItem('kahuna_admin_token');
                const user = localStorage.getItem('kahuna_admin_user');

                const formData = new FormData(productForm);

                const res = await fetch(`${BASE_URI}admin/product`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Api-Key': token,
                        'X-Api-User': user
                    }
                });

                if (res.status === 201) {
                    showMessage('Product created successfully', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    const errorData = await res.json();
                    throw new Error(errorData.message || 'Failed to create product. Please try again.');
                }
            } catch (error) {
                showMessage(error.message, 'danger');
            }
        });
    }
}

async function bindUsers() {
    try {
        const token = localStorage.getItem('kahuna_admin_token');
        const user = localStorage.getItem('kahuna_admin_user');

        const validToken = await isValidToken(token, user);

        if (!validToken) {
            showMessage('Token is invalid. Redirecting to login page.', 'danger');
            setTimeout(() => {
                window.location.href = 'admin_login.html';
            }, 2000);
            return;
        }

        const response = await fetch(`${BASE_URI}admin/users`, {
            method: 'GET',
            headers: {
                'X-Api-Key': token,
                'X-Api-User': user
            }
        });

        if (response.ok) {
            const responseData = await response.json();
            const users = responseData.data.users;
            const userList = document.getElementById('userList');

            if (!userList) {
                console.error('User list element not found in the DOM.');
                return;
            }

            if (users.length === 0) {
                userList.innerHTML = '<tr><td colspan="5" class="text-muted text-center">No users found.</td></tr>';
                return;
            }

            userList.innerHTML = '';

            users.forEach(user => {
                const listItem = document.createElement('tr');
                listItem.innerHTML = `
                    <td>${user.name}</td>
                    <td>${user.surname}</td>
                    <td>${user.email}</td>
                    <td>${user.role}</td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-user" data-id="${user.id}">Edit</button>
                    </td>
                `;
                userList.appendChild(listItem);
            });

            $('#userTable').DataTable();

            $('.edit-user').click(handleEditUser);
        } else {
            showMessage('Failed to fetch users. Please try again.', 'danger');
            console.error('Failed to fetch users');
        }
    } catch (error) {
        console.error('Error fetching users:', error);
        showMessage('Error fetching users. Please try again later.', 'danger');
    }
}

function handleEditUser() {
    const userId = $(this).data('id');

    // Fetch user data to populate the modal for editing
    fetch(`${BASE_URI}admin/user/${userId}`, {
        method: 'GET',
        headers: {
            'X-Api-Key': localStorage.getItem('kahuna_admin_token'),
            'X-Api-User': localStorage.getItem('kahuna_admin_user')
        }
    })
        .then(response => response.json())
        .then(data => {
            const user = data.data.user;

            // Populate modal fields with existing user data
            $('#editUserId').val(user.id);
            $('#editUserName').val(user.name);
            $('#editUserSurname').val(user.surname);
            $('#editUserEmail').val(user.email);
            $('#editUserRole').val(user.role);
            $('#editUserModalLabel').text('Edit User');
            $('#editUserModal').modal('show');

            // Update user details on form submission
            $('#editUserForm').on('submit', function (event) {
                event.preventDefault();

                const formData = {
                    id: $('#editUserId').val(),
                    name: $('#editUserName').val(),
                    surname: $('#editUserSurname').val(),
                    email: $('#editUserEmail').val(),
                    role: $('#editUserRole').val()
                };

                // Send updated user data to the server
                fetch(`${BASE_URI}admin/user/update/${userId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Api-Key': localStorage.getItem('kahuna_admin_token'),
                        'X-Api-User': localStorage.getItem('kahuna_admin_user')
                    },
                    body: JSON.stringify(formData)
                })
                    .then(response => response.json())
                    .then(data => {
                        $('#userModal').modal('hide');
                        showMessage('User updated successfully.', 'success');
                        setTimeout(() => {
                            window.location.href = 'admin_users.html';
                        }, 2000);
                    })
                    .catch(error => {
                        showMessage('Error updating user. Please try again later.', 'danger');
                    });
            });
        })
        .catch(error => {
            showMessage('Error fetching user data. Please try again later.', 'danger');
        });
}

function bindUserForm() {
    $('#userForm').submit(function (event) {
        event.preventDefault();

        const userId = $('#userId').val();
        const name = $('#userName').val();
        const surname = $('#userSurname').val();
        const password = $('#userPassword').val();
        const email = $('#userEmail').val();
        const role = $('#userRole').val();

        const url = userId ? `${BASE_URI}admin/user/update/${userId}` : `${BASE_URI}admin/user/create`;
        const method = userId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Api-Key': localStorage.getItem('kahuna_admin_token'),
                'X-Api-User': localStorage.getItem('kahuna_admin_user')
            },
            body: JSON.stringify({ name, surname, email, password, role })
        })
            .then(response => {
                if (response.ok) {
                    $('#userModal').modal('hide');
                    showMessage('User saved successfully.', 'success');
                    setTimeout(() => {
                        window.location.href = 'admin_users.html';
                    }, 1000);
                    bindUsers();
                } else {
                    showMessage('Failed to save user. Please try again.', 'danger');
                }
            })
            .catch(error => {
                showMessage('Error saving user. Please try again later.', 'danger');
            });
    });
}

async function bindTickets() {
    try {
        const token = localStorage.getItem('kahuna_admin_token');
        const user = localStorage.getItem('kahuna_admin_user');

        // Check token validity before fetching tickets
        const validToken = await isValidToken(token, user);

        if (!validToken) {
            showMessage('Token is invalid. Redirecting to login page.', 'danger');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
            return;
        }

        const response = await fetch(`${BASE_URI}admin/tickets`, {
            method: 'GET',
            headers: {
                'X-Api-Key': token,
                'X-Api-User': user
            }
        });

        const responseData = await response.json();
        const tickets = responseData.data.tickets;

        const ticketsContainer = document.getElementById('currentTickets');
        if (!ticketsContainer) {
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
                        <p class="card-text">Serial Number: ${ticket.product_serial_number}</p>
                    </div>
                    <div class="card-footer">
                        <a href="admin_ticket_replies.html?ticket_id=${ticket.id}&title=${encodeURIComponent(ticket.title)}&description=${encodeURIComponent(ticket.issue_description)}&status=${encodeURIComponent(ticket.status)}" class="btn btn-primary" data-ticket-id="${ticket.id}">View Details</a>
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
        showMessage('Error fetching user tickets. Please try again later.', 'danger');
    }
}

async function displayTicketReplies(ticketId) {
    try {
        const token = localStorage.getItem('kahuna_admin_token');
        const user = localStorage.getItem('kahuna_admin_user');

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
                console.error('Ticket replies container not found.');
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
function bindReplyForm() {
    const replyForm = document.getElementById('replyForm');
    replyForm.addEventListener('submit', async (evt) => {
        evt.preventDefault(); // Prevent the default form submission

        const replyContent = document.getElementById('replyContent').value;
        const ticketId = window.currentTicketId; // Use the globally stored ticket ID
        const userId = localStorage.getItem('kahuna_admin_user'); // Assuming user ID is stored in local storage

        if (replyContent.trim() === '') {
            showMessage('Reply content cannot be empty.', 'danger');
            return;
        }

        try {
            await submitReply(ticketId, userId, replyContent);
            displayTicketReplies(ticketId); // Reload replies after submission
            replyForm.reset(); // Clear the form fields
        } catch (error) {
            showMessage('Failed to submit reply', 'danger');
        }
    });
}

function bindStatusUpdateForm() {
    const statusUpdateForm = document.getElementById('statusUpdateForm');
    statusUpdateForm.addEventListener('submit', async (evt) => {
        evt.preventDefault(); // Prevent the default form submission

        const ticketStatus = document.getElementById('ticketStatus').value; // Get selected status
        const ticketId = window.currentTicketId; // Use the globally stored ticket ID

        try {
            await updateTicketStatus(ticketId, ticketStatus); // Update ticket status
            showMessage('Ticket status updated successfully', 'success');
        } catch (error) {
            console.error('Error updating ticket status:', error);
            showMessage('Failed to update ticket status', 'danger');
        }
    });
}

async function submitReply(ticket_id, user_id, reply_message) {
    const token = localStorage.getItem('kahuna_admin_token');
    const user = localStorage.getItem('kahuna_admin_user');

    const validToken = await isValidToken(token, user);

    if (validToken) {
        try {
            const formData = new FormData();
            formData.append('ticketId', ticket_id);
            formData.append('userId', user_id);
            formData.append('replyMessage', reply_message);

            console.log('payload:', formData);

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
        showMessage('Token is invalid. Redirecting to login page.', 'danger');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    }
}

async function updateTicketStatus(ticketId, newStatus) {
    try {
        const token = localStorage.getItem('kahuna_admin_token');
        const user = localStorage.getItem('kahuna_admin_user');

        const formData = new FormData();
        formData.append('ticketId', ticketId);
        formData.append('newStatus', newStatus);

        const response = await fetch(`${BASE_URI}admin/tickets/update/${ticketId}`, {
            method: 'POST',
            headers: {
                'X-Api-Key': token,
                'X-Api-User': user
            },
            body: formData
        });

        if (!response.ok) {
            throw new Error('Failed to update ticket status');
        }

        const responseData = await response.json();

        showMessage('Ticket status updated successfully', 'success');
        setTimeout(() => {
            window.location.href = 'admin_tickets.html';
        }, 2000);

    } catch (error) {
        showMessage('Failed to update ticket status. Please try again.', 'danger');
    }
}

function showMessage(message, type = 'danger') {
    const alertElement = document.getElementById('error-message');
    if (alertElement) {
        alertElement.textContent = message;
        alertElement.className = `alert alert-${type}`;
        alertElement.style.display = 'block';
        setTimeout(() => {
            alertElement.style.display = 'none';
        }, 3000);
    } else {
        console.error("Element with ID 'error-message' not found in the DOM.");
    }
}

