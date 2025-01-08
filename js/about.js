var loggedIn = JSON.parse(localStorage.getItem('loggedIn')) || false;
newPostBtn = document.getElementById('new-post-btn');
async function loadCommentsForPost(postId) {
    try {
        const response = await fetch(`./backend/comments.php?id_hilo=${postId}`);
        const result = await response.json();
        if (result.success) {
            const comments = result.comments || [];
            const commentSection = document.querySelector(`.post[data-id-hilo="${postId}"] .comments-section`);

            comments.forEach(comment => {
                // comment => { Id_Comentario, Id_Usuario, Comentario, FechaComentario, NombreUsuario }
                const commentHtml = `
                    <div class="comment">
                        <img src="./img/perfil.jpg" alt="Foto de perfil" style="height: 30px; width: 30px;">
                        <span>${comment.NombreUsuario}: ${comment.Comentario}</span>
                    </div>`;
                commentSection.insertAdjacentHTML('beforeend', commentHtml);
            });
        } else {
            console.error('Error al obtener comentarios:', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function loadPosts() {
    try {
        const response = await fetch('./backend/posts.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const result = await response.json();
        if (result.success) {
            const postsContainer = document.getElementById('post-feed');
            postsContainer.innerHTML = ''; // Limpiar publicaciones anteriores
            const hilos = result.posts || [];
            if (hilos.length === 0) {
                // Si no hay hilos, mostrar el mensaje centrado
                postsContainer.innerHTML = `
                    <div style="text-align: center; margin-top: 20px; color: black">
                        Aún no hay publicaciones, ¡sé el primero en crear un hilo!
                    </div>
                `;
            } else {
                for (const post of result.posts) {
                    // Verificar si el post pertenece al usuario actual
                    const esPropietario = parseInt(localStorage.getItem('userId')) === post.Id_Usuario;

                    const deleteButtonHtml = esPropietario
                        ? `<button class="btn-delete-post" onclick="deletePost(${post.Id_Hilo})">&times;</button>`
                        : '';

                    const postHtml = `
                      <div class="post" data-id-hilo="${post.Id_Hilo}">
                        <div class="row">
                          <div class="col-1 vote-buttons">
                            <button onclick="voteUp(this)">&#x25B2;</button>
                            <span class="vote-count">${post.Puntuacion ?? 0}</span>
                            <button onclick="voteDown(this)">&#x25BC;</button>
                          </div>
                          <div class="col-11">
                            <div class="user-info">
                              <img src="./img/perfil.jpg" alt="Foto de perfil" style="height: 30px; width: 30px;">
                              <span>${post.NombreUsuario}</span>
                              ${deleteButtonHtml}
                            </div>
                            <div class="post-title">${post.Titulo}</div>
                            <div class="post-content">${post.Asunto}</div>
                            <div class="comments-section">
                              <div class="comment-input">
                                <input type="text" class="form-control" placeholder="Escribe un comentario..." id="comment-input-${post.Id_Hilo}">
                                <button class="btn btn-dark" onclick="addComment(${post.Id_Hilo})">></button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>`;
                    postsContainer.insertAdjacentHTML('beforeend', postHtml);

                    // Esperar a cargar comentarios de este post
                    await loadCommentsForPost(post.Id_Hilo);
                }
            }

        } else {
            console.error('No se pudieron cargar los posts:', result.message);
        }
    } catch (error) {
        console.error('Error al cargar los posts:', error);
    }
}

async function votePost(idHilo, voto) {
    if (!localStorage.getItem('loggedIn')) {
        Swal.fire('Oops', 'Debes iniciar sesión para votar.', 'error');
        return;
    }

    try {
        const response = await fetch('./backend/votos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id_hilo: idHilo,
                voto: voto // 1 o -1
            })
        });
        const result = await response.json();
        if (result.success) {
            Swal.fire('Éxito', result.message, 'success');
            // (Opcional) Actualizar visualmente la puntuación del post
            // loadPosts() o un approach parcial
        } else {
            Swal.fire('Oops', result.message, 'error');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'No se pudo procesar el voto.', 'error');
    }
}
async function deletePost(idHilo) {
    // Confirmar con el usuario
    const { isConfirmed } = await Swal.fire({
        title: '¿Eliminar este post?',
        text: 'No podrás revertir esta acción',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!isConfirmed) return;

    try {
        const response = await fetch('./backend/posts.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id_hilo=${encodeURIComponent(idHilo)}`
        });
        const result = await response.json();
        if (result.success) {
            Swal.fire('Éxito', result.message, 'success');
            loadPosts && loadPosts(); // Recargar la lista de posts
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Hubo un problema al eliminar el post.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadPosts();
    if (!loggedIn) {
        // Ocultar el botón si no está iniciado sesión
        newPostBtn.style.display = 'none';
    } else {
        // Mostrar el botón si está iniciado sesión
        newPostBtn.style.display = 'inline-block';
    }
});
function voteUp(button) {
    const postElement = button.closest('.post');
    const postId = postElement.getAttribute('data-id-hilo'); // deberías asignar data-id-hilo="123" cuando renderizas
    votePost(postId, 1);
}

function voteDown(button) {
    const postElement = button.closest('.post');
    const postId = postElement.getAttribute('data-id-hilo');
    votePost(postId, -1);
}

async function addComment(postId) {
    if (!localStorage.getItem('loggedIn')) {
        Swal.fire('Oops', 'Debes iniciar sesión para comentar.', 'error');
        return;
    }
    const input = document.getElementById(`comment-input-${postId}`);
    const commentText = input.value.trim();
    if (!commentText) return; // No hagas nada si está vacío

    try {
        // 1. Enviar el comentario al servidor
        const response = await fetch('./backend/comments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id_hilo: postId,
                comentario: commentText
            })
        });
        const result = await response.json();

        if (result.success) {
            // 2. Insertar el comentario en el DOM local
            const { NombreUsuario, Comentario } = result.data;

            const commentTemplate = `
                <div class="comment">
                    <img src="./img/perfil.jpg" alt="Foto de perfil" style="height: 30px; width: 30px;">
                    <span>${NombreUsuario}: ${Comentario}</span>
                </div>`;
            input.closest('.comments-section').insertAdjacentHTML('beforeend', commentTemplate);
            input.value = '';
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error("Error al enviar el comentario:", error);
        Swal.fire('Error', 'No se pudo enviar el comentario.', 'error');
    }
}

function showLoginModal() {
    Swal.fire({
        title: 'Iniciar Sesión',
        showCloseButton: true,
        html: `
            <form id="login-form">
                <div class="mb-3">
                    <label for="swal-username" class="form-label">Usuario</label>
                    <input type="text" id="swal-username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="swal-password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <input type="password" id="swal-password" class="form-control" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                        <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark w-100">Iniciar Sesión</button>
            </form>
        `,
        showConfirmButton: false,
        didOpen: () => {
            // Manejo de la lógica de mostrar/ocultar contraseña
            const toggleBtn = document.getElementById('toggle-password');
            const passwordField = document.getElementById('swal-password');

            toggleBtn.addEventListener('click', () => {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    toggleBtn.innerHTML = `<i class="bi bi-eye-slash"></i>`; // Cambia el ícono
                } else {
                    passwordField.type = 'password';
                    toggleBtn.innerHTML = `<i class="bi bi-eye"></i>`; // Ícono de ojo
                }
            });

            // Manejo de envío del formulario
            const loginForm = document.getElementById('login-form');
            loginForm.addEventListener('submit', async function (event) {
                event.preventDefault(); // Evitar el envío del formulario por defecto

                // Obtener valores de los campos
                const username = document.getElementById('swal-username').value.trim();
                const password = document.getElementById('swal-password').value.trim();

                try {
                    // Enviar solicitud POST al backend
                    const response = await fetch('./backend/conexion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `usuario=${encodeURIComponent(username)}&contrasena=${encodeURIComponent(password)}`,
                    });

                    if (response.ok) {
                        const result = await response.json(); // Procesar la respuesta como JSON

                        if (result.success) {
                            localStorage.setItem('loggedIn', true);
                            localStorage.setItem('userId', result.userId);
                            localStorage.setItem('nombre', result.nombre);
                            // Inicio de sesión exitoso
                            Swal.fire('¡Éxito!', 'Inicio de sesión correcto.', 'success');
                            // Actualizar el estado del botón de login
                            newPostBtn.style.display = 'inline-block';
                            Swal.close(); // Cerrar el modal de SweetAlert
                        } else {
                            Swal.fire('Oops', 'Credenciales incorrectas.', 'error');
                        }
                    } else {
                        Swal.fire('Oops', 'Error al procesar la solicitud.', 'error');
                    }
                } catch (error) {
                    console.error("Error durante el inicio de sesión:", error);
                    Swal.fire('Oops', 'Ocurrió un error inesperado. Intenta más tarde.', 'error');
                }
            });
        },
    });
}

document.getElementById('login-btn').addEventListener('click', function () {
    if (!localStorage.getItem('loggedIn')) {
        showLoginModal();
    } else {
        // Cerrar sesión
        Swal.fire({
            title: '¿Cerrar sesión?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                localStorage.removeItem('loggedIn');
                localStorage.removeItem('userId');
                localStorage.removeItem('nombre');
                location.reload();
            }
        });
    }
});

document.getElementById('new-post-btn').addEventListener('click', function () {
    // Verificar si el usuario está logueado
    if (!localStorage.getItem('loggedIn')) {
        Swal.fire('Oops', 'Debes iniciar sesión para crear un post.', 'error');
        return;
    }

    // Mostrar el formulario con SweetAlert2
    Swal.fire({
        title: 'Crear Nuevo Post',
        showCloseButton: true,
        html: `
        <form id="new-post-form-swal">
          <div class="mb-3 text-start">
            <label for="swal-post-title" class="form-label">Título</label>
            <input type="text" id="swal-post-title" class="form-control" required>
          </div>
          <div class="mb-3 text-start">
            <label for="swal-post-content" class="form-label">Contenido</label>
            <textarea id="swal-post-content" class="form-control" rows="3" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100">Publicar</button>
        </form>
      `,
        showConfirmButton: false,
        allowOutsideClick: false,
        didOpen: () => {
            // Al abrir el SweetAlert, enlazamos el submit de nuestro formulario
            const newPostForm = document.getElementById('new-post-form-swal');
            newPostForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                // Recopilar datos del formulario
                const titleValue = document.getElementById('swal-post-title').value.trim();
                const contentValue = document.getElementById('swal-post-content').value.trim();

                if (!titleValue || !contentValue) {
                    Swal.fire('Oops', 'Completa los campos de Título y Contenido.', 'error');
                    return;
                }

                try {
                    // Petición para crear el post en la base de datos
                    const response = await fetch('./backend/posts.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            titulo: titleValue,
                            asunto: contentValue,
                        }),
                    });

                    const result = await response.json();
                    if (result.success) {
                        Swal.fire('Éxito', 'Post creado correctamente.', 'success').then(() => {
                            // Cerrar el modal
                            Swal.close();
                            // (Opcional) Recargar la lista de posts
                            loadPosts && loadPosts();
                        });
                    } else {
                        Swal.fire('Error', result.message || 'No se pudo crear el post.', 'error');
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire('Error', 'Hubo un problema al crear el post.', 'error');
                }
            });
        },
    });
});

// Evento de envío en el formulario "new-post-form"
document.addEventListener('submit', async (event) => {
    if (event.target && event.target.id === 'new-post-form') {
        event.preventDefault();

        // Obtener valores
        const titleValue = document.getElementById('post-title').value.trim();
        const contentValue = document.getElementById('post-content').value.trim();

        if (!titleValue || !contentValue) {
            Swal.fire('Oops', 'Completa los campos de Título y Contenido.', 'error');
            return;
        }

        try {
            // Enviar datos al backend
            const response = await fetch('./backend/posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    titulo: titleValue,
                    asunto: contentValue
                })
            });

            const result = await response.json();
            if (result.success) {
                Swal.fire('Éxito', 'Post creado correctamente.', 'success')
                    .then(() => {
                        // Cerrar el modal
                        const newPostModalEl = document.getElementById('newPostModal');
                        if (newPostModalEl) {
                            newPostModalEl.remove(); // Opcional: remover el modal del DOM
                        }
                        // (Opcional) Recargar la lista de posts
                        loadPosts();
                    });
            } else {
                Swal.fire('Error', result.message || 'No se pudo crear el post.', 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Hubo un problema al crear el post.', 'error');
        }
    }
});

function enableCommenting() {
    // Habilitar los comentarios
    console.log("Ahora puedes comentar.");
}

function enableNewPost() {
    // Mostrar la opción de crear un nuevo post
    const newPostBtn = document.createElement('button');
    newPostBtn.classList.add('btn', 'btn-primary', 'my-3');
    newPostBtn.textContent = "Crear Nuevo Post";
    newPostBtn.addEventListener('click', function () {
        console.log("Función para crear un nuevo post.");
    });
    document.getElementById('post-feed').insertAdjacentElement('beforebegin', newPostBtn);
}