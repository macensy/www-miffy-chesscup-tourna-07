<?php
/**
 * Email template: Player Login Notification
 * Usage: include this file after setting $name and $date
 * Returns: $body (HTML string)
 */
function getLoginEmailBody(string $name, string $date): string {
    return "
    <div style='font-family: Georgia, serif; max-width: 600px; margin: auto; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.4);'>
        <div style='background: linear-gradient(135deg, #3B1F0E 0%, #6B3A2A 50%, #B5622A 100%); padding: 40px 30px; text-align: center;'>
            <div style='font-size: 48px; margin-bottom: 12px;'>&#9820;</div>
            <h1 style='color: #FAF0DC; margin: 0; font-size: 24px; letter-spacing: 4px; text-transform: uppercase;'>Miffy Chess Cup</h1>
            <p style='color: #E8A96A; margin: 8px 0 0; font-size: 11px; letter-spacing: 3px; text-transform: uppercase;'>Tournament Portal</p>
        </div>
        <div style='background: #1C0A04; padding: 36px 30px;'>
            <div style='border-left: 3px solid #D4824A; padding-left: 16px; margin-bottom: 28px;'>
                <h2 style='color: #FAF0DC; margin: 0 0 6px; font-size: 20px; letter-spacing: 1px;'>Login Notification</h2>
                <p style='color: #E8A96A; margin: 0; font-size: 12px; letter-spacing: 2px; text-transform: uppercase;'>Player Activity Alert</p>
            </div>
            <p style='color: rgba(255,255,255,0.65); font-size: 14px; line-height: 1.7; margin-bottom: 24px;'>A player has just logged in to the Miffy Chess Cup tournament portal.</p>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 13px 16px; background: rgba(107,58,42,0.35); border-bottom: 1px solid rgba(212,130,74,0.15); color: #E8A96A; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; width: 36%;'>Player Name</td>
                    <td style='padding: 13px 16px; background: rgba(107,58,42,0.15); border-bottom: 1px solid rgba(212,130,74,0.15); color: #FAF0DC; font-weight: bold; font-size: 15px;'>{$name}</td>
                </tr>
                <tr>
                    <td style='padding: 13px 16px; background: rgba(107,58,42,0.35); color: #E8A96A; font-size: 11px; letter-spacing: 2px; text-transform: uppercase;'>Date &amp; Time</td>
                    <td style='padding: 13px 16px; background: rgba(107,58,42,0.15); color: rgba(255,255,255,0.7); font-size: 14px;'>{$date}</td>
                </tr>
            </table>
        </div>
        <div style='background: #0F0502; padding: 20px 30px; text-align: center; border-top: 1px solid rgba(212,130,74,0.2);'>
            <p style='color: #E8A96A; font-size: 13px; margin: 0 0 4px; letter-spacing: 1px;'>&#9820; Miffy Chess Cup</p>
            <p style='color: rgba(255,255,255,0.25); font-size: 11px; margin: 0;'>This is an automated notification. Please do not reply to this email.</p>
        </div>
    </div>";
}