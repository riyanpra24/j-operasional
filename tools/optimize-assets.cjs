const fs = require('fs');
const path = require('path');
const zlib = require('zlib');
const { promisify } = require('util');

const sharpModule = process.env.SHARP_MODULE || 'sharp';
const sharp = require(sharpModule);
const root = path.resolve(__dirname, '..');
const gzip = promisify(zlib.gzip);

async function resizeWebp(source, target, width) {
    await sharp(path.join(root, source))
        .resize({ width, withoutEnlargement: true })
        .webp({ lossless: true, effort: 6 })
        .toFile(path.join(root, target));
}

async function gzipAsset(file) {
    const source = path.join(root, file);
    if (! fs.existsSync(source)) return;

    const compressed = await gzip(fs.readFileSync(source), { level: zlib.constants.Z_BEST_COMPRESSION });
    fs.writeFileSync(`${source}.gz`, compressed);
}

async function main() {
    const cssPath = path.join(root, 'public/assets/app.css');
    let css = fs.readFileSync(cssPath, 'utf8');
    const embeddedIcon = /\.general-nav-icon\s*\{\s*--nav-image:url\("data:image\/png;base64,([^"]+)"\);\s*\}/;
    const match = css.match(embeddedIcon);

    if (match) {
        await sharp(Buffer.from(match[1], 'base64'))
            .resize(64, 64, { fit: 'contain' })
            .webp({ lossless: true, effort: 6 })
            .toFile(path.join(root, 'public/assets/images/general-section-64.webp'));
        css = css.replace(
            embeddedIcon,
            '.general-nav-icon { --nav-image:url("images/general-section-64.webp"); }'
        );
        fs.writeFileSync(cssPath, css);
    }

    await Promise.all([
        resizeWebp('public/assets/jamkrindo-kanwil-surabaya.png', 'public/assets/jamkrindo-kanwil-surabaya.webp', 520),
    ]);

    await Promise.all([
        gzipAsset('public/assets/app.min.css'),
        gzipAsset('public/assets/app.min.js'),
        gzipAsset('public/assets/required-markers.min.js'),
        gzipAsset('public/assets/url-mask.min.js'),
    ]);
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
