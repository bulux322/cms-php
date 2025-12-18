<?php
include "includes/header.php";
include "includes/db.php";
include "includes/navigation.php";

if (!isset($_GET['p_id'])) {
    header("Location: index.php");
    exit;
}

$the_post_id = (int) $_GET['p_id'];

/* ===============================
   HANDLE COMMENT SUBMISSION
================================ */
if (isset($_POST['create_comment'])) {

    $comment_author  = trim($_POST['comment_author'] ?? '');
    $comment_email   = trim($_POST['comment_email'] ?? '');
    $comment_content = trim($_POST['comment_content'] ?? '');
    $author_type     = $_POST['author_type'] ?? 'guest';

    if ($comment_author && $comment_email && $comment_content) {

        date_default_timezone_set("Asia/Dhaka");
        $comment_date = date('D, F d, Y - h:i:s A');

        $comment_status = ($author_type === 'user') ? 'approve' : 'unapprove';

        $query = "
            INSERT INTO comments 
            (author_type, comment_post_id, comment_author, comment_email, comment_content, comment_status, comment_date)
            VALUES
            ('$author_type', $the_post_id, '$comment_author', '$comment_email', '$comment_content', '$comment_status', '$comment_date')
        ";

        if (!mysqli_query($connection, $query)) {
            die('Comment insert failed: ' . mysqli_error($connection));
        }

        // update comment count
        mysqli_query(
            $connection,
            "UPDATE posts SET post_comment_count = post_comment_count + 1 WHERE post_id = $the_post_id"
        );

        $_SESSION['comment_message'] = ($author_type === 'user')
            ? "Thanks."
            : "Waiting for admin approval.";

        header("Location: post.php?p_id={$the_post_id}");
        exit;
    }
}
?>

    <div class="container">
        <div class="row">
            <div class="col-md-8">
            <?php
            /* ===============================
            UPDATE VIEW COUNT
            ================================ */
                mysqli_query(
                    $connection,
                    "UPDATE posts SET post_views_count = post_views_count + 1 WHERE post_id = $the_post_id"
                );

            /* ===============================
            FETCH POST
            ================================ */
                $post_query = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'
                    ? "SELECT * FROM posts WHERE post_id = $the_post_id"
                    : "SELECT * FROM posts WHERE post_id = $the_post_id AND post_status = 'publish'";

                $post_result = mysqli_query($connection, $post_query);

                if (mysqli_num_rows($post_result) < 1) {
                    echo "<div class='alert alert-info'>Post not available.</div>";
                    exit;
                }

                $post = mysqli_fetch_assoc($post_result);
            ?>

            <h2><?= htmlspecialchars($post['post_title']) ?></h2>
            <p class="lead">
                by <a href="author_post.php?author=<?= $post['post_author'] ?>"><?= $post['post_author'] ?></a>
            </p>
            <p><span class="glyphicon glyphicon-time"></span> <?= $post['post_date'] ?></p>

            <p>
                <?php
                foreach (explode(',', $post['post_tags']) as $tag) {
                    $labels = ['primary','default','success','info','warning','danger'];
                    echo "<span class='label label-{$labels[array_rand($labels)]}'>$tag</span> ";
                }
                ?>
            </p>

            <hr>
            <img class="img-responsive" src="images/post_pic/<?= $post['post_image'] ?>" alt="">
            <hr>

            <p><?= $post['post_content'] ?></p>
            <hr>

            <!-- ===============================
                FLASH MESSAGE
            ================================ -->
            <div class="well">
                <?php
                if (isset($_SESSION['comment_message'])) {
                    echo "<div class='alert alert-success'><strong>Comment Added!</strong> {$_SESSION['comment_message']}</div>";
                    unset($_SESSION['comment_message']);
                }
                ?>

            <!-- ===============================
                COMMENT FORM
            ================================ -->
            <h4>Leave a Comment:</h4>

            <form method="post">
                <input type="hidden" name="author_type" value="<?= isset($_SESSION['username']) ? 'user' : 'guest' ?>">

            <?php if (isset($_SESSION['username'])): ?>
                <input type="hidden" name="comment_author" value="<?= $_SESSION['username'] ?>">
                <input type="hidden" name="comment_email" value="<?= $_SESSION['email'] ?>">
                <p><strong><?= $_SESSION['username'] ?></strong></p>
            <?php else: ?>
                <input class="form-control" name="comment_author" placeholder="Name" required><br>
                <input class="form-control" name="comment_email" placeholder="Email" required><br>
            <?php endif; ?>

                <textarea class="form-control" name="comment_content" rows="3" required></textarea><br>
                <button class="btn btn-primary" name="create_comment">Submit</button>
            </form>
            </div>

            <hr>

            <!-- ===============================
                COMMENTS LIST
            ================================ -->
            <?php
            $comments_query = "
                SELECT * FROM comments 
                WHERE comment_post_id = $the_post_id 
                AND comment_status = 'approve'
                ORDER BY comment_id DESC
            ";
            $comments = mysqli_query($connection, $comments_query);

            while ($comment = mysqli_fetch_assoc($comments)):
                $author_image = 'guest.png';

                if ($comment['author_type'] === 'user') {
                    $img = mysqli_fetch_assoc(
                        mysqli_query($connection, "SELECT user_image FROM users WHERE username='{$comment['comment_author']}'")
                    );
                    $author_image = $img['user_image'] ?? 'no_avatar.gif';
                }
            ?>
            <div class="media">
                <div class="media-left">
                    <img src="images/user_pic/<?= $author_image ?>" class="media-object" width="45">
                </div>
                <div class="media-body">
                    <h4><?= $comment['comment_author'] ?>
                        <small>(<?= $comment['comment_date'] ?>)</small>
                    </h4>
                    <p><?= htmlspecialchars($comment['comment_content']) ?></p>

            <?php if (!empty($comment['comment_reply'])): ?>
                    <div class="media">
                        <div class="media-left">
                            <img src="images/user_pic/admin.jpeg" class="media-object" width="45">
                        </div>
                        <div class="media-body">
                            <h4 style="color:red;">Admin
                                <small>(<?= $comment['comment_reply_date'] ?>)</small>
                            </h4>
                            <p><?= htmlspecialchars($comment['comment_reply']) ?></p>
                        </div>
                    </div>
            <?php endif; ?>

    </div>
</div>
<?php endwhile; ?>

</div>
<?php include "includes/sidebar.php"; ?>
</div>
<hr>
<?php include "includes/footer.php"; ?>