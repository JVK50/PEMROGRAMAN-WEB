// ========== Hamburger Menu (JS Driven, replace checkbox hack) ==========
function initNavToggle() {
    const toggleBtn = document.getElementById("nav-toggle-btn");
    const nav = document.querySelector("header nav");
    if (!toggleBtn || !nav) return;

    toggleBtn.addEventListener("click", function () {
        nav.classList.toggle("nav-open");
    });
}

function updateCounter() {
    const rows = document.querySelectorAll(".table-responsive table tbody tr");
    const counterEl = document.getElementById("table-counter");
    
    if (!counterEl) return; // Jika elemen counter tidak ada di halaman ini, hentikan

    if (rows.length === 0) {
        counterEl.textContent = "Tidak ada data.";
        return;
    }

    let visibleCount = 0;
    rows.forEach(function (row) {
        if (row.style.display !== "none") {
            visibleCount++;
        }
    });

    counterEl.textContent = `Menampilkan ${visibleCount} dari ${rows.length} data`;
}

// ===== Konfirmasi hapus (front-end only, belum ke server) =====
function initHapusConfirm() {
    document.querySelectorAll(".btn-hapus").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const row = btn.closest("tr");
            const nama = row ? row.querySelector("td")?.textContent : "data ini";
            const yakin = confirm("Yakin ingin menghapus \"" + nama + "\"?");
            if (yakin && row) {
                row.remove();
                updateCounter(); // Update counter setelah menghapus baris
            }
        });
    });
}

// ===== Filter/pencarian tabel real-time =====
// function initTableFilter() {
//     const input = document.getElementById("search-input");
//     const table = document.querySelector(".table-responsive table");
//     if (!input || !table) return;

//     input.addEventListener("keyup", function () {
//         const keyword = input.value.toLowerCase();
//         const rows = table.querySelectorAll("tbody tr");
//         rows.forEach(function (row) {
//             const teks = row.textContent.toLowerCase();
//             row.style.display = teks.includes(keyword) ? "" : "none";
//         });
//     });
// }
function initTableFilter() {
    const input = document.getElementById("search-input");
    const table = document.querySelector(".table-responsive table");
    if (!input || !table) return;

    input.addEventListener("keyup", function () {
        const keyword = input.value.toLowerCase();
        const rows = table.querySelectorAll("tbody tr");
        rows.forEach(function (row) {
            const kolomTarget = row.querySelectorAll("td")[1];
            if (kolomTarget) {
                const teks = kolomTarget.textContent.toLowerCase();
                row.style.display = teks.includes(keyword) ? "" : "none";
            }
        });
        updateCounter();
    });
}

// ===== Validasi form (client-side) =====
function tampilkanError(input, pesan) {
    hapusError(input);
    const span = document.createElement("span");
    span.className = "error";
    span.textContent = pesan;
    input.insertAdjacentElement("afterend", span);
}

function hapusError(input) {
    const next = input.nextElementSibling;
    if (next && next.classList.contains("error")) {
        next.remove();
    }
}

// function initValidasiForm() {
//     const form = document.getElementById("form-tambah");
//     if (!form) return;

//     form.addEventListener("submit", function (e) {
//         let valid = true;

//         const judul = form.querySelector("[name='judul'], [name='nama']");
//         if (judul && judul.value.trim() === "") {
//             tampilkanError(judul, "Field ini wajib diisi.");
//             valid = false;
//         } else if (judul) {
//             hapusError(judul);
//         }

//         const pengarang = form.querySelector("[name='pengarang']");
//         if (pengarang && pengarang.value.trim() === "") {
//             tampilkanError(pengarang, "Pengarang wajib diisi.");
//             valid = false;
//         } else if (pengarang) {
//             hapusError(pengarang);
//         }

//         const tahun = form.querySelector("[name='tahun']");
//         if (tahun) {
//             const nilai = parseInt(tahun.value, 10);
//             if (isNaN(nilai) || nilai < 1900 || nilai > 2026) {
//                 tampilkanError(tahun, "Tahun harus di antara 1900-2026.");
//                 valid = false;
//             } else {
//                 hapusError(tahun);
//             }
//         }

//         const stok = form.querySelector("[name='stok']");
//         if (stok) {
//             const nilai = parseInt(stok.value, 10);
//             if (isNaN(nilai) || nilai < 0) {
//                 tampilkanError(stok, "Stok tidak boleh negatif.");
//                 valid = false;
//             } else {
//                 hapusError(stok);
//             }
//         }

//         const isbn = form.querySelector("[name='isbn']");
//         if (isbn && isbn.value.trim() !== "") {
//             // Regex untuk memeriksa hanya angka dan tanda hubung
//             const isbnRegex = /^[0-9-]+$/; 
//             if (!isbnRegex.test(isbn.value.trim())) {
//                 tampilkanError(isbn, "ISBN hanya boleh berisi angka dan tanda hubung (-).");
//                 valid = false;
//             } else {
//                 hapusError(isbn);
//             }
//         }

//         if (!valid) {
//             e.preventDefault();
//         }
//     });
// }

function initValidasiForm() {
    const form = document.getElementById("form-tambah");
    if (!form) return;

    form.addEventListener("submit", function (e) {
        let valid = true;

        const rules = [
            { name: "nama", message: "Nama wajib diisi.", type: "required" },
            { name: "judul", message: "Judul wajib diisi.", type: "required" },
            { name: "pengarang", message: "Pengarang wajib diisi.", type: "required" },
            { name: "no_anggota", message: "No. Anggota wajib diisi.", type: "required" },
            { name: "tahun", message: "Tahun harus di antara 1900-2026.", type: "range", min: 1900, max: 2026 },
            { name: "stok", message: "Stok tidak boleh negatif.", type: "min", min: 0 },
            { name: "isbn", message: "ISBN hanya boleh angka dan tanda hubung.", type: "regex", pattern: /^[0-9-]+$/ }
        ];

        rules.forEach(function(rule) {
            const input = form.querySelector(`[name='${rule.name}']`);
            if (!input) return; 

            let isValid = true;
            const value = input.value.trim();

            if (rule.type === "required" && value === "") {
                isValid = false;
            } else if (rule.type === "range" && value !== "") {
                const num = parseInt(value, 10);
                if (isNaN(num) || num < rule.min || num > rule.max) isValid = false;
            } else if (rule.type === "min" && value !== "") {
                const num = parseInt(value, 10);
                if (isNaN(num) || num < rule.min) isValid = false;
            } else if (rule.type === "regex" && value !== "") {
                if (!rule.pattern.test(value)) isValid = false;
            }

            if (!isValid) {
                tampilkanError(input, rule.message);
                valid = false;
            } else {
                hapusError(input);
            }
        });

        if (!valid) {
            e.preventDefault(); 
        }
    });
}

document.addEventListener("DOMContentLoaded", function () {
    initNavToggle();
    initHapusConfirm();
    initTableFilter();
    initValidasiForm();
    updateCounter(); // Update counter saat halaman dimuat
});