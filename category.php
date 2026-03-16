<?php
require_once __DIR__."/config.php";
$mysqli = db();

$slug = $_GET['slug'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page,1);

$limit = 24;
$offset = ($page - 1) * $limit;


/* GET CATEGORY */
$stmt = $mysqli->prepare("
SELECT 
id,
name,
seo_title,
seo_description,
seo_keywords,
seo_canonical,
seo_robots,
seo_h1,
seo_h2,
seo_intro,
seo_bottom_content
FROM categories
WHERE slug=?
LIMIT 1
");

$stmt->bind_param("s",$slug);
$stmt->execute();
$cat = $stmt->get_result()->fetch_assoc();

if(!$cat){
http_response_code(404);
exit("Category not found");
}


/* COUNT MOVIES */

$countStmt = $mysqli->prepare("SELECT COUNT(*) FROM movies WHERE category_id=?");
$countStmt->bind_param("i",$cat['id']);
$countStmt->execute();
$countStmt->bind_result($totalMovies);
$countStmt->fetch();
$countStmt->close();

$totalPages = max(1, ceil($totalMovies/$limit));


/* CANONICAL */

if($page > 1){
$canonical = $BASE_URL."/category/".$slug."/page/".$page;
}else{
$canonical = $BASE_URL."/category/".$slug;
}


/* URL HELPER */

function cat_page_url($slug,$page,$BASE_URL){
if($page == 1){
return $BASE_URL."/category/".$slug;
}
return $BASE_URL."/category/".$slug."/page/".$page;
}


/* MOVIES */

$movies = $mysqli->prepare("
SELECT id,title,slug,poster_path
FROM movies
WHERE category_id=?
ORDER BY id DESC
LIMIT ? OFFSET ?
");

$movies->bind_param("iii",$cat['id'],$limit,$offset);
$movies->execute();
$result = $movies->get_result();


/* LOAD ALL CATEGORIES FOR HEADER */

$cats = $mysqli->query("
SELECT id,name,slug
FROM categories
ORDER BY name
")->fetch_all(MYSQLI_ASSOC);

$category_id = $cat['id'];
$search = '';



/* SEO FROM DATABASE */

$title = $cat['seo_title'] ?? '';
$description = $cat['seo_description'] ?? '';
$keywords = $cat['seo_keywords'] ?? '';
$robots = $cat['seo_robots'] ?? 'index,follow';

if(!empty($cat['seo_canonical'])){
$canonical = $cat['seo_canonical'];
}


/* PAGE CONTENT SEO */

$h1 = !empty($cat['seo_h1']) ? $cat['seo_h1'] : $cat['name']." Movies Download";
$h2 = !empty($cat['seo_h2']) ? $cat['seo_h2'] : "Latest ".$cat['name']." Movies";
$intro = $cat['seo_intro'] ?? '';
$bottom = $cat['seo_bottom_content'] ?? '';



/* LOAD LAYOUT */

include "partials/head.php";
include "partials/header.php";
?>

<main class="container">

<h1><?=h($h1)?></h1>

<?php if(!empty($intro)): ?>
<p class="category-intro"><?=nl2br(h($intro))?></p>
<?php endif; ?>


<h2><?=h($h2)?></h2>

<?php include "sections/category-grid.php"; ?>


<?php include "sections/category-pagination.php"; ?>


<?php if(!empty($bottom)): ?>

<div class="category-seo-text">

<h2>About <?=h($cat['name'])?> Movies</h2>

<p><?=nl2br(h($bottom))?></p>

</div>

<?php endif; ?>

</main>

<?php include "partials/footer.php"; ?>