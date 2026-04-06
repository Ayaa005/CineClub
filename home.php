<?php
include("config.php");
include ("auth.php");

// récupérer la prochaine séance
$sql = "SELECT events.*, movies.title, movies.poster_url,
        COUNT(participants.id) AS nb_participants
        FROM events
        JOIN movies ON events.movie_id = movies.id
        LEFT JOIN participants ON events.id = participants.event_id
        GROUP BY events.id
        ORDER BY events.event_date ASC
        LIMIT 1";

$result = $conn->query($sql);
$events = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <!-- CSS -->
    <link rel="stylesheet" href="style.css">

    <!-- ICONS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<!-- HERO (reste statique) -->
<section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>
            CINE<span class="club-text">CLUB</span>
        </h1>
        <p>
            Organize unforgettable movie nights with your friends.
            Vote,<br> plan, snack, and enjoy cinema together.
        </p>
        <div class="hero-button">
            <a href="voting.php" class="btn-red">
                <i class="fa-solid fa-check-to-slot"></i> Start Voting
            </a>
            <a href="planning.php" class="btn-dark">
                <i class="fa-solid fa-calendar"></i> View Schedule
            </a>
        </div>
    </div>
</section>

<!-- NEXT SESSION (dynamique) -->
<section class="next-session">
    <h2>NEXT <span>SESSION</span></h2>

<?php if($events){ ?>

    <div class="session-card">

        <!-- IMAGE dynamique -->
        <img src="<?php echo $events['poster_url']; ?>" />

        <div class="session-info">

            <!-- TITRE -->
            <h3><?php echo $events['title']; ?></h3>

            <!-- DATE + HEURE + PARTICIPANTS -->
            <p>
                <i class="fa-solid fa-calendar"></i>
                <?php echo $events['event_date']; ?>

                <i class="fa-solid fa-clock"></i>
                <?php echo $events['heure']; ?>

                <i class="fa-solid fa-user-group"></i>
                <?php echo $events['nb_participants']; ?> attending
            </p>

            <a href="participants.php?id=<?php echo $events['id']; ?>">
                View details >
            </a>

        </div>
    </div>

<?php } else { ?>
    <p>Aucune séance disponible 😢</p>
<?php } ?>

</section>

</body>
</html>