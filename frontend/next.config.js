/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  // Don't fail the production build on lint warnings.
  eslint: { ignoreDuringBuilds: true },
};
module.exports = nextConfig;
