// src/plugins/utils.js
import moment from "moment";
import { usePage } from "@inertiajs/vue3";

/**
 * Kumpulkan nama permission unik dari semua role user (Spatie).
 * @param {object|null} user
 * @returns {Set<string>}
 */
export function collectUserPermissionNames(user) {
  const names = new Set();
  user?.roles?.forEach((role) => {
    role.permissions?.forEach((p) => names.add(p.name));
  });
  return names;
}

/**
 * Cek akses permission — mendukung OR dengan `|` dan wildcard akhiran `.*`
 * (selaras pola route `permission:logbook.*`).
 * @param {object|null} user
 * @param {string} pattern contoh: `logbook.*`, `report-logbook`, `a|b`
 */
export function userCan(user, pattern) {
  if (!user || !pattern) {
    return false;
  }
  const parts = String(pattern)
    .split("|")
    .map((s) => s.trim())
    .filter(Boolean);
  if (parts.length === 0) {
    return false;
  }
  return parts.some((p) => matchOnePermissionPattern(user, p));
}

function matchOnePermissionPattern(user, pattern) {
  const names = collectUserPermissionNames(user);
  if (names.size === 0) {
    return false;
  }
  if (pattern.endsWith(".*")) {
    const prefix = pattern.slice(0, -2);
    return [...names].some(
      (n) => n === pattern || n.startsWith(`${prefix}.`)
    );
  }
  return names.has(pattern);
}

export default {
  install(app) {
    // Fungsi untuk memeriksa apakah array memiliki item
    app.config.globalProperties.$hasItems = (array) => {
      return Array.isArray(array) && array.length > 0;
    };

    // Fungsi untuk memformat tanggal
    app.config.globalProperties.$formatDate = ({
      date,
      formatSource = "YYYY-MM-DD hh:mm",
      formatOutput = "DD/MM/YYYY",
    }) => {
      return moment(date, formatSource).format(formatOutput);
    };

    app.config.globalProperties.$hasRoles = (roleNames) => {
      // Ubah roleNames menjadi array jika berupa string
      const roleList = Array.isArray(roleNames) ? roleNames : [roleNames];

      // Cek apakah user memiliki salah satu dari role yang ada di roleList
      return usePage().props.auth.user.roles?.some((role) =>
        roleList.includes(role.name)
      );
    };

    app.config.globalProperties.$hasPerms = (permNames) => {
      const permList = Array.isArray(permNames) ? permNames : [permNames];

      return usePage().props.auth.user.roles?.some((role) =>
        role.permissions.some((permission) =>
          permList.includes(permission.name)
        )
      );
    };

    /** Pakai ini untuk menyelaraskan menu dengan middleware route (termasuk wildcard `nama.*`). */
    app.config.globalProperties.$can = (pattern) =>
      userCan(usePage().props.auth.user, pattern);

    app.config.globalProperties.$truncatedText = (text) => {
      const words = text ? text.split(" ") : "";
      if (words.length > 5) {
        return words.slice(0, 5).join(" ") + "...";
      } else {
        return text;
      }
    };

    app.config.globalProperties.$snakeCaseText = (text) => {
      return text
        .replace(/([a-z])([A-Z])/g, "$1_$2") // Menambahkan garis bawah sebelum huruf kapital
        .replace(/\s+/g, "_") // Mengganti spasi dengan garis bawah
        .toLowerCase(); // Mengubah semua huruf menjadi kecil
    };

    app.config.globalProperties.$storagePath = (path) => {
      return `/storage/${path}`;
    };
  },
};
