<?php
// --- Konfigurasi Awal ---
$data_file = 'tasks.txt'; // File untuk menyimpan data tugas
$tasks = []; // Array untuk menyimpan semua tugas

// --- Fungsi Helper ---

// Fungsi untuk membaca tugas dari file dan menangani data lama
function loadTasks($file) {
    if (file_exists($file)) {
        $json_data = file_get_contents($file);
        $raw_tasks = json_decode($json_data, true) ?: [];

        $cleaned_tasks = [];
        foreach ($raw_tasks as $task) {
            // Pastikan setiap tugas memiliki key 'id', 'title', dan 'status'
            // Berikan nilai default jika key tidak ada (untuk kompatibilitas mundur)
            $cleaned_tasks[] = [
                'id' => $task['id'] ?? uniqid(), // Gunakan ID lama atau buat baru (uniqid() penuh)
                'title' => $task['title'] ?? $task['description'] ?? 'Tugas Tanpa Deskripsi', // 'title' atau fallback ke 'description'
                'status' => $task['status'] ?? ($task['completed'] ?? false ? 'selesai' : 'belum') // 'status' atau fallback dari 'completed'
            ];
        }
        return $cleaned_tasks;
    }
    return [];
}

// Fungsi untuk menyimpan tugas ke file
function saveTasks($file, $tasks_array) {
    file_put_contents($file, json_encode($tasks_array, JSON_PRETTY_PRINT));
}

// Muat tugas saat halaman dimuat
$tasks = loadTasks($data_file);

// --- Proses Aksi (Tambah, Ubah Status, Hapus) ---

// 1. Tambah Tugas
if (isset($_POST['action']) && $_POST['action'] === 'add_task') {
    $description = trim($_POST['description'] ?? '');
    if (!empty($description)) {
        $new_task = [
            'id' => uniqid(), // Tetap pakai uniqid() untuk ID unik internal
            'title' => $description, // Menggunakan 'title'
            'status' => 'belum' // Status default: "belum"
        ];
        $tasks[] = $new_task;
        saveTasks($data_file, $tasks);
    }
    header('Location: index.php'); // Redirect untuk mencegah resubmission form
    exit();
}

// 2. Ubah Status Tugas
if (isset($_GET['action']) && $_GET['action'] === 'change_status' && isset($_GET['id'])) {
    $task_id = $_GET['id'];
    foreach ($tasks as &$task) { // Gunakan & untuk referensi agar bisa mengubah array asli
        if ($task['id'] === $task_id) {
            // Pastikan key 'status' ada sebelum diakses
            $current_status = $task['status'] ?? 'belum'; // Fallback jika 'status' tidak ada
            $task['status'] = ($current_status === 'belum') ? 'selesai' : 'belum';
            break;
        }
    }
    saveTasks($data_file, $tasks);
    header('Location: index.php');
    exit();
}

// 3. Hapus Tugas
if (isset($_GET['action']) && $_GET['action'] === 'delete_task' && isset($_GET['id'])) {
    $task_id = $_GET['id'];
    $tasks = array_filter($tasks, function($task) use ($task_id) {
        // Pastikan key 'id' ada sebelum diakses
        return ($task['id'] ?? null) !== $task_id;
    });
    $tasks = array_values($tasks); // Re-index array setelah filter
    saveTasks($data_file, $tasks);
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List Harian Jeki</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <div class="container my-5 p-4 bg-white rounded shadow-sm">
        <h1 class="text-center mb-4 custom-title">Daftar Tugas Harianmu</h1>

        <div class="mb-4">
            <form action="index.php" method="POST" class="d-flex">
                <input type="hidden" name="action" value="add_task">
                <input type="text" name="description" class="form-control me-2" placeholder="Apa yang perlu dilakukan hari ini?" required>
                <button type="submit" class="btn btn-primary custom-btn-primary">Tambah Tugas</button>
            </form>
        </div>

        <ul class="list-group">
            <?php if (empty($tasks)): ?>
                <li class="list-group-item text-center text-muted fst-italic">Belum ada tugas. Ayo tambahkan yang pertama!</li>
            <?php else: ?>
                <?php $display_index = 1; // Inisialisasi nomor urut tampilan ?>
                <?php foreach ($tasks as $task): ?>
                    <?php
                        // Memastikan semua key ada sebelum ditampilkan
                        $taskId = htmlspecialchars($task['id'] ?? 'N/A'); // Ini ID unik internal
                        $taskTitle = htmlspecialchars($task['title'] ?? 'Tugas tidak dikenal');
                        $taskStatus = htmlspecialchars($task['status'] ?? 'belum'); // Default 'belum'
                        $isCompleted = ($taskStatus === 'selesai');
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center mb-2 rounded shadow-sm-custom <?php echo $isCompleted ? 'list-group-item-light task-completed' : ''; ?>">
                        <div class="d-flex align-items-center flex-grow-1">
                            <a href="index.php?action=change_status&id=<?php echo $taskId; ?>" class="me-3 custom-checkbox-wrapper">
                                <span class="custom-checkbox <?php echo $isCompleted ? 'checked' : ''; ?>"></span>
                            </a>
                            <span class="badge bg-secondary me-3">No: <?php echo $display_index++; ?></span>
                            <span class="task-title <?php echo $isCompleted ? 'text-decoration-line-through text-muted' : ''; ?>">
                                <?php echo $taskTitle; ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge <?php echo $isCompleted ? 'bg-success' : 'bg-warning text-dark'; ?> me-3">
                                <?php echo ucfirst($taskStatus); ?>
                            </span>
                            <a href="index.php?action=delete_task&id=<?php echo $taskId; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus tugas ini?');">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcqpvfLZxSs1g65GqtmhwtzBqU3y" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>