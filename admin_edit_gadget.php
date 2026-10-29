<?php
require 'db.php';
if (!isAdmin()) redirect('index.php');

if (!isset($_GET['id'])) {
    redirect('admin_gadgets.php');
}

$id = (int)$_GET['id'];

// Fetch current gadget
$res = mysqli_query($conn, "SELECT * FROM gadgets WHERE id = $id");
$gadget = mysqli_fetch_assoc($res);

if (!$gadget) {
    redirect('admin_gadgets.php');
}

// Handle delete gallery image
if (isset($_GET['del_img'])) {
    $img_id = (int)$_GET['del_img'];
    $img_check = mysqli_query($conn, "SELECT image FROM gadget_images WHERE id = $img_id AND gadget_id = $id");
    if ($img_row = mysqli_fetch_assoc($img_check)) {
        if (file_exists("uploads/" . $img_row['image'])) {
            @unlink("uploads/" . $img_row['image']);
        }
        mysqli_query($conn, "DELETE FROM gadget_images WHERE id = $img_id AND gadget_id = $id");
    }
    redirect("admin_edit_gadget.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $daily_price = (float)$_POST['daily_price'];
    $category_id = (int)$_POST['category_id'];
    
    // Check if new primary image uploaded
    $image_query = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image);
        $image_query = ", image = '$image'";
    }
    
    mysqli_query($conn, "UPDATE gadgets SET title = '$title', description = '$description', daily_price = $daily_price, category_id = $category_id $image_query WHERE id = $id");
    
    // Check if additional gallery images uploaded
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        foreach ($_FILES['gallery_images']['name'] as $k => $name) {
            if ($_FILES['gallery_images']['error'][$k] == 0) {
                $ext_img = time() . '_' . $k . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
                if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$k], "uploads/" . $ext_img)) {
                    mysqli_query($conn, "INSERT INTO gadget_images (gadget_id, image) VALUES ($id, '$ext_img')");
                }
            }
        }
    }
    
    redirect("admin_edit_gadget.php?id=$id");
}

// Fetch categories for dropdown
$res_cat = mysqli_query($conn, "SELECT * FROM categories");
$categories = [];
if ($res_cat) {
    while ($row = mysqli_fetch_assoc($res_cat)) {
        $categories[] = $row;
    }
}

// Fetch gallery images
$gallery_res = mysqli_query($conn, "SELECT * FROM gadget_images WHERE gadget_id = $id");
$gallery_images = [];
if ($gallery_res) {
    while ($row = mysqli_fetch_assoc($gallery_res)) {
        $gallery_images[] = $row;
    }
}

require 'header.php';
?>

<h2>Edit Gadget</h2>

<div style="background: var(--container-bg); padding:30px; border:1px solid var(--border-color); margin-bottom: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto;">
    <form method="POST" enctype="multipart/form-data">
        <label>Title:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($gadget['title']); ?>" required>
        
        <label>Description:</label>
        <textarea name="description" rows="4"><?php echo htmlspecialchars($gadget['description']); ?></textarea>
        
        <label>Daily Rental Price (₹):</label>
        <input type="number" step="0.01" name="daily_price" value="<?php echo htmlspecialchars($gadget['daily_price']); ?>" required>
        
        <label>Category:</label>
        <select name="category_id" required>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $gadget['category_id'] == $c['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label>Current Image:</label><br>
        <?php if ($gadget['image']): ?>
            <img src="uploads/<?php echo $gadget['image']; ?>" width="100" style="border-radius: 5px; margin-bottom: 10px;">
        <?php else: ?>
            <p>No image currently.</p>
        <?php endif; ?>
        <br>
        
        <label>Upload New Primary Image (Optional - leaves current image if empty):</label>
        <input type="file" name="image">

        <label style="margin-top: 15px;">Additional Gallery Images:</label>
        <?php if (!empty($gallery_images)): ?>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                <?php foreach ($gallery_images as $gimg): ?>
                    <div style="position: relative; border: 1px solid var(--border-color); border-radius: 8px; padding: 4px; background: var(--input-bg);">
                        <img src="uploads/<?php echo htmlspecialchars($gimg['image']); ?>" width="80" height="80" style="object-fit: cover; border-radius: 6px; display: block;">
                        <a href="admin_edit_gadget.php?id=<?php echo $id; ?>&del_img=<?php echo $gimg['id']; ?>" 
                           onclick="return confirm('Delete this gallery image?');" 
                           style="display: block; text-align: center; color: #ef4444; font-size: 0.75rem; font-weight: 700; margin-top: 4px; text-decoration: none;">Delete</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;">No additional gallery images uploaded.</p>
        <?php endif; ?>

        <label>Add More Gallery Images (Multiple selection supported):</label>
        <input type="file" name="gallery_images[]" multiple>
        
        <button type="submit" class="btn" style="width: 100%;">Save Changes</button>
        <div style="text-align:center; margin-top: 15px;">
            <a href="admin_gadgets.php" style="color: var(--text-color); text-decoration:none;">&larr; Cancel</a>
        </div>
    </form>
</div>

<?php require 'footer.php'; ?>
