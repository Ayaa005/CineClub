let movies = [];
let activeFilter = "all";

const genreEmojis = {
    "Action": "⚔️",
    "Thriller": "🔪",
    "Sci-Fi": "🚀",
    "Drama": "🎭",
    "Comedy": "😂",
    "Horror": "👻",
    "Romance": "💝",
    "Animation": "🎨"
};

// LOAD
function loadMovies() {
    fetch("get_movies.php")
        .then(res => res.json())
        .then(data => {
            movies = data;
            updateStats();
            renderCards();
        });
}

// STARS
function buildStars(r) {
    let html = "";
    for (let i = 1; i <= 5; i++) {
        html += i <= r ? "★" : '<span class="empty">★</span>';
    }
    return html;
}

// STATS
function updateStats() {
    document.getElementById("total-chip").textContent =
        "🎬 " + movies.length + " movie nights";

    let avg = movies.reduce((a, b) => a + Number(b.rating || 0), 0) / (movies.length || 1);

    document.getElementById("avg-chip").textContent =
        "⭐ Avg rating: " + avg.toFixed(1);
}

// RENDER
function renderCards() {
    const grid = document.getElementById("archiveGrid");
    const query = document.getElementById("searchInput").value.toLowerCase();

    const filtered = movies.filter(m =>
        (activeFilter === "all" || m.genre === activeFilter) &&
        m.title.toLowerCase().includes(query)
    );

    if (filtered.length === 0) {
        grid.innerHTML = `<div class="empty-state">🎬 No movies</div>`;
        return;
    }

    grid.innerHTML = filtered.map((m) => `
        <div class="movie-card" onclick="openDetail(${movies.indexOf(m)})">
            <div class="poster-placeholder">${genreEmojis[m.genre] || "🎬"}</div>
            <div class="movie-info">
                <div class="movie-title">${m.title}</div>
                <div class="movie-meta">${m.year} · ${m.date}</div>
                <div class="stars">${buildStars(m.rating)}</div>
            </div>
        </div>
    `).join("");
}

// FILTER
function filterBy(g, btn) {
    activeFilter = g;
    document.querySelectorAll(".filter-btn").forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
    renderCards();
}

// ADD MOVIE
function submitMovie() {
    let attendees = [];
    document.querySelectorAll("#attendee-picker input:checked").forEach(cb => {
        attendees.push(cb.dataset.name);
    });

    let formData = new FormData();
    formData.append("title", document.getElementById("f-title").value);
    formData.append("year", document.getElementById("f-year").value);
    formData.append("genre", document.getElementById("f-genre").value);
    formData.append("date", document.getElementById("f-date").value);
    formData.append("rating", document.getElementById("f-rating").value);
    formData.append("comment", document.getElementById("f-comment").value);
    formData.append("attendees", JSON.stringify(attendees));

    fetch("add_movie.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === "success") {
            document.getElementById("addModal").classList.remove("open");
            loadMovies();
        }
    });
}

// MODAL
function openDetail(i) {
    let m = movies[i];
    document.getElementById("modalContent").innerHTML =
        `<h2>${m.title}</h2><p>${m.comment}</p>`;
    document.getElementById("detailModal").classList.add("open");
}

function closeModal(e) {
    if (e.target.id === "detailModal") {
        document.getElementById("detailModal").classList.remove("open");
    }
}

function openAddModal() {
    document.getElementById("addModal").classList.add("open");
}

function closeAddModal(e) {
    if (!e || e.target.id === "addModal") {
        document.getElementById("addModal").classList.remove("open");
    }
}

// INIT
document.addEventListener("DOMContentLoaded", loadMovies);