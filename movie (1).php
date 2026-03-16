<?php
require_once __DIR__."/config.php";
$mysqli = db();

$id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$slug = $_GET['slug'] ?? '';

/* =========================
   FETCH MOVIE
========================= */

if($slug){

$stmt=$mysqli->prepare("
SELECT 
m.*, 
c.name AS category_name,
c.slug AS category_slug,
ROUND(IFNULL(AVG(r.rating),0),1) AS avg_rating,
COUNT(r.id) AS total_votes
FROM movies m
LEFT JOIN categories c ON m.category_id=c.id
LEFT JOIN ratings r ON m.id=r.movie_id
WHERE m.slug=?
GROUP BY m.id
");

$stmt->bind_param("s",$slug);

}else{

$stmt=$mysqli->prepare("
SELECT 
m.*, 
c.name AS category_name,
c.slug AS category_slug,
ROUND(IFNULL(AVG(r.rating),0),1) AS avg_rating,
COUNT(r.id) AS total_votes
FROM movies m
LEFT JOIN categories c ON m.category_id=c.id
LEFT JOIN ratings r ON m.id=r.movie_id
WHERE m.id=?
GROUP BY m.id
");

$stmt->bind_param("i",$id);

}

$stmt->execute();
$movie=$stmt->get_result()->fetch_assoc();

if(!$movie){
http_response_code(404);
exit("Movie not found");
}

/* =========================
   REDIRECT OLD ID URL
========================= */

if($id && !empty($movie['slug'])){
header("Location: ".$BASE_URL."/movie/".$movie['slug'],true,301);
exit;
}

$id=$movie['id'];

/* =========================
   POSTER URL
========================= */

$poster=$movie['poster_is_external']
? $movie['poster_path']
: ($UPLOAD_URL."/".basename($movie['poster_path']));

/* =========================
   TAGS
========================= */

$tags=array_filter(array_map('trim',explode(',',$movie['tags'] ?? '')));

/* =========================
   RELATED MOVIES
========================= */

$related=[];

if(!empty($tags)){

$likeParts=[];
$params=[];
$types="";

foreach($tags as $tag){
$likeParts[]="tags LIKE ?";
$params[]="%$tag%";
$types.="s";
}

$sql="SELECT id,title,poster_path,poster_is_external,slug
FROM movies
WHERE id!=? AND (".implode(" OR ",$likeParts).")
ORDER BY created_at DESC
LIMIT 4";

$stmtR=$mysqli->prepare($sql);
$stmtR->bind_param("i".$types,$id,...$params);
$stmtR->execute();
$related=$stmtR->get_result()->fetch_all(MYSQLI_ASSOC);

}

/* =========================
   RECENT MOVIES
========================= */

$recent=$mysqli->query("
SELECT id,title,poster_path,poster_is_external,slug
FROM movies
ORDER BY created_at DESC
LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

/* =========================
   POPULAR MOVIES
========================= */

$popular=$mysqli->query("
SELECT 
m.id,
m.title,
m.poster_path,
m.poster_is_external,
m.slug,
ROUND(IFNULL(AVG(r.rating),0),1) avg_rating
FROM movies m
LEFT JOIN ratings r ON m.id=r.movie_id
GROUP BY m.id
ORDER BY avg_rating DESC
LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

/* =========================
   SEO META
========================= */

$canonical = $BASE_URL."/movie/".($movie['slug'] ?? $movie['id']);

$title = $movie['title']." Download 480p 720p 1080p HDRip | ".$SITE_NAME;

$description = substr(strip_tags($movie['description'] ?? ''),0,160);

/* =========================
   LOAD CATEGORIES
========================= */

$cats = $mysqli->query("
SELECT id,name,slug
FROM categories
ORDER BY name
")->fetch_all(MYSQLI_ASSOC);

$category_id = $movie['category_id'] ?? 0;
$search = '';

/* =========================
   LOAD LAYOUT
========================= */

include "partials/head.php";
include "partials/header.php";
?>

<main class="container">

<?php include "sections/movie-detail.php"; ?>

<?php include "sections/screenshots.php"; ?>

<?php include "sections/downloads.php"; ?>

<?php include "sections/share.php"; ?> 

<?php include "sections/rate.php"; ?>

<?php include "sections/comment_section.php"; ?>

<?php include "sections/related.php"; ?>

<?php include "sections/recent.php"; ?>

<?php include "sections/popular.php"; ?>

</main>

<?php include "partials/footer.php"; ?>