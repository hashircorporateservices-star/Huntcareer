import "./globals.css";
import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "HuntCareer",
  description: "Your Scouts find, tailor, and prepare job applications. You review and send.",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
