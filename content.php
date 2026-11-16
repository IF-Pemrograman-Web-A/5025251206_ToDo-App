<?php
// content.php - Komponen Konten Utama Modular (Daftar Tugas, Detail Card, dan Form CRUD)

$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, fn($t) => $t['is_completed'] == 1));
$pendingTasks = $totalTasks - $completedTasks;

$viewTitle = ($currentView === 'shared') ? '👥 Shared Tasks (Organisasi)' : '👤 Personal Tasks';
$viewSubtitle = ($currentView === 'shared') 
    ? 'Daftar tugas kolaboratif yang dibagikan ke seluruh anggota tim Iron Citadel' 
    : 'Daftar tugas pribadi Anda untuk produktivitas harian';

// Task yang ditampilkan di panel detail (bisa dari edit atau default pertama)
$selectedTask = $editingTask ?? ($tasks[0] ?? null);
?>

<div class="content-wrapper">
    <!-- Panel Kiri: Task List & Summary -->
    <main class="left-panel" role="main">
        <section class="todo-list-container" aria-labelledby="list-heading">
            <div class="panel-header">
                <div>
                    <h2 id="list-heading" class="panel-title"><?= $viewTitle; ?></h2>
                    <p class="panel-subtitle"><?= $viewSubtitle; ?></p>
                </div>
                <div class="stats-pills">
                    <span class="pill pill-pending" title="Tugas Belum Selesai"><?= $pendingTasks; ?> Pending</span>
                    <span class="pill pill-completed" title="Tugas Selesai"><?= $completedTasks; ?> Selesai</span>
                </div>
            </div>

            <!-- List Todos -->
            <ul class="todo-list" id="todo-list" aria-live="polite">
                <?php if (empty($tasks)): ?>
                    <li class="empty-state">
                        <div class="empty-icon">📂</div>
                        <h3>Belum ada tugas di kategori ini</h3>
                        <p>Silakan buat tugas baru melalui form di samping kanan.</p>
                    </li>
                <?php else: ?>
                    <?php foreach ($tasks as $task): 
                        $isDone = (bool)$task['is_completed'];
                        $dueTimestamp = strtotime($task['due_date']);
                        $isOverdue = !$isDone && ($dueTimestamp < strtotime(date('Y-m-d')));
                        $isToday = !$isDone && ($dueTimestamp === strtotime(date('Y-m-d')));
                    ?>
                        <li class="todo-item <?= $isDone ? 'is-completed' : ''; ?> <?= $isOverdue ? 'is-overdue' : ''; ?>" id="task-item-<?= $task['id']; ?>">
                            <div class="todo-header-row">
                                <div class="todo-title-group">
                                    <!-- Checkbox Toggle Status Selesai (CRUD: Update Status) -->
                                    <form action="actions.php" method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $task['id']; ?>">
                                        <input type="hidden" name="view" value="<?= htmlspecialchars($currentView); ?>">
                                        <input type="checkbox" 
                                               class="task-checkbox"
                                               aria-label="Tandai <?= htmlspecialchars($task['title']); ?> sebagai <?= $isDone ? 'belum selesai' : 'selesai'; ?>"
                                               <?= $isDone ? 'checked' : ''; ?> 
                                               onchange="this.form.submit();">
                                    </form>

                                    <!-- Judul Tugas (Klik untuk melihat detail di card) -->
                                    <button type="button" 
                                            class="task-title-btn <?= $isDone ? 'completed-task' : ''; ?>" 
                                            onclick="showTaskDetail(<?= htmlspecialchars(json_encode($task), ENT_QUOTES, 'UTF-8'); ?>)"
                                            aria-label="Lihat detail <?= htmlspecialchars($task['title']); ?>">
                                        <?= htmlspecialchars($task['title']); ?>
                                    </button>
                                </div>

                                <div class="todo-tags">
                                    <span class="tag tag-category tag-<?= $task['category']; ?>">
                                        <?= ucfirst($task['category']); ?>
                                    </span>
                                    <?php if (!empty($task['file_path'])): ?>
                                        <span class="tag tag-attachment" title="Memiliki lampiran file/gambar">📎 File</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="todo-meta-row">
                                <span class="due-info <?= $isOverdue ? 'overdue-text' : ($isToday ? 'today-text' : ''); ?>">
                                    📅 Batas Waktu: <?= format_date($task['due_date']); ?>
                                    <?= $isOverdue ? '<strong>(Terlambat)</strong>' : ($isToday ? '<strong>(Hari Ini)</strong>' : ''); ?>
                                </span>

                                <?php if (!empty($task['notif_time'])): ?>
                                    <span class="notif-info" data-notif="<?= htmlspecialchars($task['notif_time']); ?>" data-title="<?= htmlspecialchars($task['title']); ?>" data-desc="<?= htmlspecialchars($task['description']); ?>">
                                        ⏰ Pengingat: <?= format_datetime($task['notif_time']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="action-btns">
                                <button type="button" 
                                        class="btn-detail" 
                                        onclick="showTaskDetail(<?= htmlspecialchars(json_encode($task), ENT_QUOTES, 'UTF-8'); ?>)" 
                                        aria-label="Lihat detail <?= htmlspecialchars($task['title']); ?>">
                                    🔍 Detail
                                </button>
                                <a href="index.php?view=<?= htmlspecialchars($currentView); ?>&edit=<?= $task['id']; ?>#todo-form-container" 
                                   class="btn-edit" 
                                   aria-label="Edit tugas <?= htmlspecialchars($task['title']); ?>">
                                    ✏️ Edit
                                </a>
                                <a href="actions.php?action=delete&id=<?= $task['id']; ?>&view=<?= htmlspecialchars($currentView); ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus tugas \'<?= htmlspecialchars(addslashes($task['title'])); ?>\'? File lampiran juga akan terhapus.');"
                                   aria-label="Hapus tugas <?= htmlspecialchars($task['title']); ?>">
                                    🗑️ Hapus
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>
    </main>

    <!-- Panel Kanan: Task Detail Card & Create/Edit Form -->
    <aside class="right-panel" role="complementary">
        <!-- 1. Task Details Card -->
        <section class="todo-detail-container" aria-labelledby="detail-heading">
            <h2 id="detail-heading" class="panel-title">Task Details</h2>
            <div class="detail-card" id="task-detail-card" aria-live="polite">
                <?php if ($selectedTask): ?>
                    <div class="detail-badge-group">
                        <span class="tag tag-category tag-<?= $selectedTask['category']; ?>">
                            <?= ucfirst($selectedTask['category']); ?>
                        </span>
                        <span class="badge-status <?= $selectedTask['is_completed'] ? 'status-completed' : 'status-pending'; ?>">
                            <?= $selectedTask['is_completed'] ? '✅ Completed' : '⏳ In Progress'; ?>
                        </span>
                    </div>

                    <h3 class="detail-title" id="detail-card-title"><?= htmlspecialchars($selectedTask['title']); ?></h3>
                    
                    <div class="detail-info-grid">
                        <p><strong>📅 Batas Waktu:</strong> <span id="detail-card-date"><?= format_date($selectedTask['due_date']); ?></span></p>
                        <p><strong>⏰ Waktu Notifikasi:</strong> <span id="detail-card-notif"><?= format_datetime($selectedTask['notif_time']); ?></span></p>
                    </div>

                    <div class="detail-desc-box">
                        <strong>Deskripsi:</strong>
                        <p id="detail-card-desc" style="white-space: pre-line;"><?= htmlspecialchars($selectedTask['description']); ?></p>
                    </div>

                    <!-- Lampiran File (PHP File Handling: Display & Download) -->
                    <div id="detail-card-attachment">
                        <?php if (!empty($selectedTask['file_path'])): 
                            $fileExt = strtolower(pathinfo($selectedTask['file_path'], PATHINFO_EXTENSION));
                            $isImg = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        ?>
                            <div class="attachment-preview-box">
                                <strong>📎 File Lampiran:</strong>
                                <?php if ($isImg): ?>
                                    <div class="img-wrapper">
                                        <img src="uploads/<?= htmlspecialchars($selectedTask['file_path']); ?>" 
                                             alt="Lampiran <?= htmlspecialchars($selectedTask['title']); ?>" 
                                             class="todo-image-preview">
                                    </div>
                                <?php endif; ?>
                                <div class="file-download-bar">
                                    <span class="file-name-label">📄 <?= htmlspecialchars($selectedTask['file_name'] ?? $selectedTask['file_path']); ?></span>
                                    <a href="uploads/<?= htmlspecialchars($selectedTask['file_path']); ?>" 
                                       download="<?= htmlspecialchars($selectedTask['file_name'] ?? 'lampiran'); ?>" 
                                       class="btn-download" 
                                       target="_blank">
                                        ⬇️ Unduh File
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-attachment"><em>Tidak ada lampiran file.</em></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="placeholder-text"><em>Klik judul tugas untuk melihat rincian detail</em></p>
                <?php endif; ?>
            </div>
        </section>

        <!-- 2. Form Create / Edit Task (CRUD + PHP File Upload) -->
        <section class="todo-form-container" id="todo-form-container" aria-labelledby="form-heading">
            <div class="form-header">
                <h2 id="form-heading" class="panel-title">
                    <?= $editingTask ? '✏️ Edit Tugas' : '➕ Buat Tugas Baru'; ?>
                </h2>
                <?php if ($editingTask): ?>
                    <a href="index.php?view=<?= htmlspecialchars($currentView); ?>" class="btn-cancel-edit" title="Batal mode edit">
                        ✕ Batal Edit
                    </a>
                <?php endif; ?>
            </div>

            <form id="todo-form" 
                  action="actions.php" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  aria-label="Task Form">
                
                <input type="hidden" name="action" value="<?= $editingTask ? 'update' : 'create'; ?>">
                <input type="hidden" name="view" value="<?= htmlspecialchars($currentView); ?>">
                <?php if ($editingTask): ?>
                    <input type="hidden" name="task_id" value="<?= $editingTask['id']; ?>">
                <?php endif; ?>

                <!-- Judul Tugas -->
                <div class="form-group">
                    <label for="task_name">Nama Tugas: <span class="required-star">*</span></label>
                    <input type="text" 
                           id="task_name" 
                           name="task_name" 
                           required 
                           aria-required="true" 
                           placeholder="Contoh: Mengerjakan modul PHP..."
                           value="<?= htmlspecialchars($editingTask['title'] ?? ''); ?>">
                </div>

                <!-- Kategori (Personal atau Shared) Sesuai Kriteria 1 -->
                <div class="form-group">
                    <label for="task_category">Kategori Tugas (Personal / Shared): <span class="required-star">*</span></label>
                    <select id="task_category" name="task_category" required class="form-select">
                        <option value="personal" <?= (($editingTask['category'] ?? $currentView) === 'personal') ? 'selected' : ''; ?>>
                            👤 Personal (Tugas Pribadi)
                        </option>
                        <option value="shared" <?= (($editingTask['category'] ?? $currentView) === 'shared') ? 'selected' : ''; ?>>
                            👥 Shared (Tugas Organisasi / Tim)
                        </option>
                    </select>
                </div>

                <!-- Deskripsi Tugas -->
                <div class="form-group">
                    <label for="task_description">Deskripsi Tugas: <span class="required-star">*</span></label>
                    <textarea id="task_description" 
                              name="task_description" 
                              rows="3" 
                              required 
                              aria-required="true" 
                              placeholder="Detail deskripsi mengenai tugas..."><?= htmlspecialchars($editingTask['description'] ?? ''); ?></textarea>
                </div>

                <!-- Batas Waktu / Due Date -->
                <div class="form-group">
                    <label for="task_deadline">Batas Waktu (Due Date): <span class="required-star">*</span></label>
                    <input type="date" 
                           id="task_deadline" 
                           name="task_deadline" 
                           required 
                           aria-required="true"
                           value="<?= htmlspecialchars($editingTask['due_date'] ?? date('Y-m-d')); ?>">
                </div>

                <!-- PHP File Handling: Unggah File / Gambar / Lampiran (Kriteria 5) -->
                <div class="form-group">
                    <label for="task_file">Lampiran File / Gambar (File Handling PHP):</label>
                    <input type="file" 
                           id="task_file" 
                           name="task_file" 
                           accept="image/*,.pdf,.txt,.doc,.docx,.zip" 
                           capture="environment">
                    <small class="form-hint">Maksimal 5MB. Format: Gambar (JPG, PNG, WebP), PDF, DOC, TXT, ZIP.</small>

                    <?php if (!empty($editingTask['file_path'])): ?>
                        <div class="current-file-box">
                            <span>File saat ini: <strong><?= htmlspecialchars($editingTask['file_name'] ?? $editingTask['file_path']); ?></strong></span>
                            <label class="checkbox-label">
                                <input type="checkbox" name="remove_existing_file" value="1"> Hapus lampiran ini
                            </label>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Waktu Notifikasi (Pengingat Service Worker) -->
                <div class="form-group">
                    <label for="task_notif">Waktu Pengingat (Notification Time):</label>
                    <input type="datetime-local" 
                           id="task_notif" 
                           name="task_notif"
                           value="<?= !empty($editingTask['notif_time']) ? date('Y-m-d\TH:i', strtotime($editingTask['notif_time'])) : ''; ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" id="submit-btn" class="btn-submit" aria-label="Simpan Tugas">
                        <?= $editingTask ? '💾 Perbarui Tugas' : '➕ Tambah Tugas'; ?>
                    </button>
                    <?php if ($editingTask): ?>
                        <a href="index.php?view=<?= htmlspecialchars($currentView); ?>" class="btn-secondary">
                            Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </aside>
</div>
