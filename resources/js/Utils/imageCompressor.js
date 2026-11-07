/**
 * Kompres dan resize file gambar di sisi browser (Client-Side) ke format JPEG (.jpg).
 *
 * Menghasilkan file gambar yang ringan (biasanya 80KB - 150KB) dari foto kamera HP (3MB - 12MB),
 * menjaga aspek rasio, menghapus metadata EXIF berat, dan memastikan format output adalah JPG standar
 * sehingga mudah diunduh dan dibuka di semua perangkat tanpa memerlukan WebP.
 *
 * @param {File} file - File gambar mentah dari input file
 * @param {Object} [options]
 * @param {number} [options.maxWidth=1000] - Batas lebar maksimum gambar dalam piksel
 * @param {number} [options.maxHeight=1000] - Batas tinggi maksimum gambar dalam piksel
 * @param {number} [options.quality=0.82] - Kualitas kompresi JPEG (0.0 sampai 1.0)
 * @returns {Promise<File>} File terkompresi berformat image/jpeg (.jpg)
 */
export function compressImageToJpeg(file, options = {}) {
    const {
        maxWidth = 1000,
        maxHeight = 1000,
        quality = 0.82,
    } = options;

    return new Promise((resolve) => {
        // Jika bukan file gambar, kembalikan apa adanya
        if (!file || !(file instanceof File) || !file.type.startsWith('image/')) {
            return resolve(file);
        }

        const reader = new FileReader();

        reader.onload = (readEvent) => {
            const img = new Image();

            img.onload = () => {
                let { width, height } = img;

                // Hitung skala resize secara proporsional agar tidak gepeng/distorsi
                if (width > maxWidth || height > maxHeight) {
                    if (width / height > maxWidth / maxHeight) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    } else {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                // Render ke HTML5 Canvas
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    return resolve(file);
                }

                // Beri latar belakang putih bersih untuk menangani transparansi PNG
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, width, height);
                ctx.drawImage(img, 0, 0, width, height);

                // Export ke format standar JPEG (.jpg)
                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            return resolve(file);
                        }

                        // Beri nama file baru berekstensi .jpg
                        const originalName = file.name.replace(/\.[^/.]+$/, '');
                        const newFileName = `${originalName}.jpg`;

                        const compressedFile = new File([blob], newFileName, {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });

                        // Gunakan hasil kompresi jika ukurannya lebih hemat
                        if (compressedFile.size < file.size) {
                            resolve(compressedFile);
                        } else {
                            resolve(file);
                        }
                    },
                    'image/jpeg',
                    quality
                );
            };

            img.onerror = () => resolve(file);
            img.src = readEvent.target.result;
        };

        reader.onerror = () => resolve(file);
        reader.readAsDataURL(file);
    });
}

