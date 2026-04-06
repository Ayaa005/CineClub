<?php
include("config.php");
include ("auth.php");

// récupérer films + votes
$sql = "SELECT movies.*,users.name, COUNT(votes.id) AS nb_votes
        FROM movies
        LEFT JOIN votes ON movies.id = votes.movie_id
        LEFT JOIN users on movies.user_id=users.id
        GROUP BY movies.id";

$result = $conn->query($sql);

if(!$result){
    die("Erreur SQL : " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Voting</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<section class="voting">

    <div class="top-bar">
        <h1>MOVIE <span>VOTING</span></h1>
        <?php if(isset($_SESSION['user_id'])): ?>
            <button class="suggest-btn" onclick="openModal()">+ Suggest Movie</button>
            <?php else: ?>
                <a href="login.php" class="suggest-btn">Login to suggest</a>
            <?php endif; ?>
    </div>

    <p>Suggest movies and vote for the next session</p>

    <input type="text" placeholder="Search movies..." class="search" id="searchInput">

    <div class="movies-container">

<?php while($movies = $result->fetch_assoc()): ?>

        <div class="movie-card">

            <img src="<?php echo $movies['poster_url']; ?>">

            <div class="movie-info">

                <span class="votes">
                    ⭐ <?php echo $movies['nb_votes']; ?> votes
                </span>

                <h3><?php echo $movies['title']; ?></h3>

                <p><?php echo $movies['annee']; ?> • organized by <?php echo $movies['name']; ?></p>

                <form method="POST" action="vote.php">
                    <input type="hidden" name="film_id" value="<?php echo $movies['id']; ?>">
                    <button class="vote-btn">Vote</button>
                </form>

            </div>
        </div>

<?php endwhile; ?>

    </div>
</section>

<!-- MODAL -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>

        <h2>Suggest a Movie 🎬</h2>

        <form method="POST" action="add_movie.php">

            <input type="text" name="titre" placeholder="Movie title" required>
            <input type="text" name="genre" placeholder="Genre">
            <input type="number" name="annee" placeholder="Year">
            <input type="text" name="image" placeholder="img/movies.jpg">
            <textarea name="description" placeholder="Description"></textarea>

            <button type="submit" class="btn-submit">Add Movie</button>
        </form>
    </div>
</div>

<script>
function openModal(){
    document.getElementById("modal").style.display = "flex";
}
function closeModal(){
    document.getElementById("modal").style.display = "none";
}
</script>
<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let cards = document.querySelectorAll(".movie-card");

    cards.forEach(card => {
        let title = card.querySelector("h3").innerText.toLowerCase();

        if(title.includes(value)){
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
});
</script>

</body>
</html>