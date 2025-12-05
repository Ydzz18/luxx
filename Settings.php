<?php
/**
 * Settings Helper Class
 * Manages site settings and calculations
 */

require_once 'config.php';

class Settings {
    
    /**
     * Calculate shipping fee based on subtotal
     */
    public static function calculateShipping($subtotal) {
        $freeShippingMin = (float) getSetting('free_shipping_min', 50000);
        $shippingFee = (float) getSetting('shipping_fee', 500);
        
        // Free shipping if subtotal meets minimum
        if ($subtotal >= $freeShippingMin) {
            return 0;
        }
        
        return $shippingFee;
    }
    
    /**
     * Calculate tax based on subtotal
     */
    public static function calculateTax($subtotal) {
        $taxRate = (float) getSetting('tax_rate', 12);
        return $subtotal * ($taxRate / 100);
    }
    
    /**
     * Get currency symbol
     */
    public static function getCurrency() {
        return getSetting('currency', '₱');
    }
    
    /**
     * Get currency code
     */
    public static function getCurrencyCode() {
        return getSetting('currency_code', 'PHP');
    }
    
    /**
     * Format price with currency
     */
    public static function formatPrice($price) {
        return self::getCurrency() . number_format($price, 2);
    }
    
    /**
     * Check if guest checkout is allowed
     */
    public static function isGuestCheckoutAllowed() {
        return getSetting('allow_guest_checkout', '1') === '1';
    }
    
    /**
     * Check if maintenance mode is active
     */
    public static function isMaintenanceMode() {
        return getSetting('maintenance_mode', '0') === '1';
    }
}