# API Lifecycle

API menggunakan versioning pada URL: `/api/v1`.

- perubahan kompatibel dapat dirilis pada v1;
- breaking change wajib menggunakan versi mayor baru;
- deprecation diumumkan melalui changelog dan header;
- migration guide disediakan sebelum penghentian versi lama;
- masa transisi ditentukan sesuai dampak dan perjanjian mitra;
- kontrak OpenAPI adalah sumber kebenaran teknis.
