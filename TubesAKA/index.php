<?php
// Hubungkan ke database
include "connect.php";

// Periksa koneksi ke database
if (!$connect) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Variabel untuk menyimpan hasil pencarian dan data waktu
$resultsIterative = []; // Hasil pencarian dengan metode iteratif
$resultsFiltering = []; // Hasil pencarian dengan metode rekursif
$searchScore = ''; // Nilai skor yang dicari
$timeIterative = []; // Waktu eksekusi metode iteratif
$timeFiltering = []; // Waktu eksekusi metode rekursif

// Fungsi pencarian iteratif
function searchIterative($data, $score) {
    global $timeIterative; // Gunakan variabel global untuk menyimpan waktu
    $found = []; // Hasil pencarian
    $startTime = microtime(true); // Mulai catat waktu

    // Periksa setiap baris data
    foreach ($data as $row) {
        $timeIterative[] = microtime(true) - $startTime; // Catat waktu saat ini
        if ((float)$row['Score'] == (float)$score) { // Jika skor cocok
            $found[] = $row; // Tambahkan ke hasil pencarian
        }
    }

    return $found; // Kembalikan hasil pencarian
}

// Fungsi filtering rekursif
function searchFiltering($data, $score, $index = 0, $found = [], $startTime = null) {
    global $timeFiltering; // Gunakan variabel global untuk menyimpan waktu

    // Mulai catat waktu jika ini adalah iterasi pertama
    if ($startTime === null) {
        $startTime = microtime(true);
    }

    $timeFiltering[] = microtime(true) - $startTime; // Catat waktu saat ini

    // Jika sudah mencapai akhir data, kembalikan hasil
    if ($index >= count($data)) {
        return $found;
    }

    // Jika skor cocok, tambahkan ke hasil pencarian
    if ((float)$data[$index]['Score'] == (float)$score) {
        $found[] = $data[$index];
    }

    // Lanjutkan ke data berikutnya
    return searchFiltering($data, $score, $index + 1, $found, $startTime);
}

// Jika form pencarian dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchScore = $_POST['score']; // Ambil skor dari input pengguna

    // Ambil semua data dari tabel anime
    $query = "SELECT * FROM anime";
    $result = mysqli_query($connect, $query);

    if ($result) {
        // Simpan semua data dalam array
        $animeData = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $animeData[] = $row;
        }

        // Gunakan metode iteratif untuk pencarian
        $resultsIterative = searchIterative($animeData, $searchScore);

        // Gunakan metode rekursif untuk pencarian
        $resultsFiltering = searchFiltering($animeData, $searchScore);
    }
}

// Tutup koneksi database
mysqli_close($connect);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian Sequential Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS sama seperti kode Anda */
    </style>
</head>

<body>
    <div class="container">
        <h1>Data Anime - Sequential Search</h1>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="number" step="any" name="score" class="form-control" placeholder="Cari berdasarkan Score" value="<?= htmlspecialchars($searchScore) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <h3>Hasil Pencarian (Iteratif):</h3>
            <?php if (!empty($resultsIterative)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>MAL_ID</th>
                            <th>Nama</th>
                            <th>Score</th>
                            <th>Genres</th>
                            <th>Episodes</th>
                            <th>Members</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultsIterative as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['MAL_ID']) ?></td>
                                <td><?= htmlspecialchars($row['Name']) ?></td>
                                <td><?= htmlspecialchars($row['Score']) ?></td>
                                <td><?= htmlspecialchars($row['Genres']) ?></td>
                                <td><?= htmlspecialchars($row['Episodes']) ?></td>
                                <td><?= htmlspecialchars($row['Members']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Tidak ada data yang ditemukan.</p>
            <?php endif; ?>

            <h3>Hasil Pencarian (Rekursif):</h3>
            <?php if (!empty($resultsFiltering)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>MAL_ID</th>
                            <th>Nama</th>
                            <th>Score</th>
                            <th>Genres</th>
                            <th>Episodes</th>
                            <th>Members</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultsFiltering as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['MAL_ID']) ?></td>
                                <td><?= htmlspecialchars($row['Name']) ?></td>
                                <td><?= htmlspecialchars($row['Score']) ?></td>
                                <td><?= htmlspecialchars($row['Genres']) ?></td>
                                <td><?= htmlspecialchars($row['Episodes']) ?></td>
                                <td><?= htmlspecialchars($row['Members']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Tidak ada data yang ditemukan.</p>
            <?php endif; ?>

            <div class="chart-container">
                <canvas id="timeChart"></canvas>
            </div>

            <script>
                var timeIterative = <?= json_encode($timeIterative) ?>;
                var timeFiltering = <?= json_encode($timeFiltering) ?>;

                // Tentukan jumlah hasil pencarian
                var resultsIterativeCount = <?= count($resultsIterative) ?>;
                var resultsFilteringCount = <?= count($resultsFiltering) ?>;

                // Sumbu X disesuaikan dengan jumlah hasil pencarian
                var searchAttemptsIterative = Array.from({
                    length: resultsIterativeCount
                }, (_, i) => i + 1);
                var searchAttemptsFiltering = Array.from({
                    length: resultsFilteringCount
                }, (_, i) => i + 1);

                // Pangkas waktu agar sesuai dengan hasil pencarian
                var timeIterativeTrimmed = timeIterative.slice(0, resultsIterativeCount);
                var timeFilteringTrimmed = timeFiltering.slice(0, resultsFilteringCount);

                if (timeIterativeTrimmed.length > 0 || timeFilteringTrimmed.length > 0) {
                    var ctx = document.getElementById('timeChart').getContext('2d');
                    var timeChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: searchAttemptsIterative.length > searchAttemptsFiltering.length ?
                                searchAttemptsIterative :
                                searchAttemptsFiltering, // Label berdasarkan hasil pencarian
                            datasets: [{
                                    label: 'Iterative Search',
                                    data: timeIterativeTrimmed,
                                    borderColor: 'blue',
                                    backgroundColor: 'rgba(0, 0, 255, 0.1)',
                                    fill: false,
                                    tension: 0.3
                                },
                                {
                                    label: 'Filtering Search',
                                    data: timeFilteringTrimmed,
                                    borderColor: 'red',
                                    backgroundColor: 'rgba(255, 0, 0, 0.1)',
                                    fill: false,
                                    tension: 0.3
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            },
                            scales: {
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Search Attempt'
                                    }
                                },
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Time (seconds)'
                                    },
                                    beginAtZero: false
                                }
                            }
                        }
                    });
                }
            </script>
        <?php endif; ?>
    </div>
</body>

</html>