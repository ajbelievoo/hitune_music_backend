<?php
/**
 * HiTune Music Distribution - Cart Page
 */
$pageTitle = 'Cart - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .cart-page { max-width: 1000px; margin: 0 auto; padding: 100px 30px 60px; }
    .cart-header { margin-bottom: 40px; }
    .cart-header h1 { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
    .cart-header p { color: rgba(255,255,255,0.6); }
    .cart-container { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; overflow: hidden; }
    .cart-table { width: 100%; }
    .cart-table th { background: rgba(255,255,255,0.05); padding: 20px; text-align: left; font-size: 14px; font-weight: 600; color: rgba(255,255,255,0.7); text-transform: uppercase; }
    .cart-table td { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .cart-item { display: flex; gap: 20px; align-items: center; }
    .cart-item-img { width: 80px; height: 80px; background: linear-gradient(135deg, #333, #555); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 30px; }
    .cart-item-info h4 { font-size: 16px; font-weight: 600; margin-bottom: 5px; }
    .cart-item-info p { font-size: 13px; color: rgba(255,255,255,0.6); margin-bottom: 8px; }
    .cart-item-info .badge { display: inline-block; padding: 4px 10px; background: rgba(255,193,7,0.2); color: #ffc107; border-radius: 4px; font-size: 12px; }
    .cart-item-info .promo { color: #00c853; font-size: 13px; }
    .cart-actions a { color: #00c853; font-size: 13px; text-decoration: none; }
    .cart-actions a.remove { color: #f44336; margin-left: 15px; }
    .cart-price { font-weight: 600; }
    .cart-summary { padding: 30px; background: rgba(255,255,255,0.03); }
    .summary-row { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .summary-row.total { border-top: 2px solid rgba(255,255,255,0.2); border-bottom: none; font-size: 20px; font-weight: 700; }
    .summary-row.total .price { color: #00c853; }
    .checkout-btn { width: 100%; padding: 18px; background: linear-gradient(135deg, #00c853, #00e676); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 25px; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .checkout-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,200,83,0.3); }
    .empty-cart { text-align: center; padding: 100px 30px; }
    .empty-cart i { font-size: 80px; color: rgba(255,255,255,0.2); margin-bottom: 30px; }
    .empty-cart h2 { font-size: 28px; margin-bottom: 15px; }
    .empty-cart p { color: rgba(255,255,255,0.6); margin-bottom: 30px; }
</style>

<div class="cart-page">
    <div class="cart-header">
        <h1>Shopping Cart</h1>
        <p>You have 4 items in your cart.</p>
    </div>

    <div class="cart-container">
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Actions</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="cart-item">
                            <div class="cart-item-img"><i class="mdi mdi-music"></i></div>
                            <div class="cart-item-info">
                                <h4>Aye Mere Watan</h4>
                                <p>Single Release - Release Date: August 15, 2024</p>
                                <span class="badge"><i class="mdi mdi-alert"></i> Some of your selections require a plan upgrade.</span>
                                <div style="margin-top: 10px;"><a href="#" style="color: rgba(255,255,255,0.6); font-size: 13px;">View Details <i class="mdi mdi-chevron-down"></i></a></div>
                            </div>
                        </div>
                    </td>
                    <td class="cart-actions">
                        <a href="#" class="promo"><i class="mdi mdi-ticket-percent"></i> Apply Promo Code</a>
                        <a href="#" class="remove"><i class="mdi mdi-delete"></i> Remove</a>
                    </td>
                    <td class="cart-price">Free with plan upgrade</td>
                </tr>
                <tr>
                    <td>
                        <div class="cart-item">
                            <div class="cart-item-img" style="background: linear-gradient(135deg, #9c27b0, #e91e63);"><i class="mdi mdi-account"></i></div>
                            <div class="cart-item-info">
                                <h4>Additional Main Artist</h4>
                                <p>1-year access unlimited releases to all Stores and Discovery Platforms under an additional Primary Artist name.</p>
                            </div>
                        </div>
                    </td>
                    <td class="cart-actions">
                        <a href="#" class="promo"><i class="mdi mdi-ticket-percent"></i> Apply Promo Code</a>
                        <a href="#" class="remove"><i class="mdi mdi-delete"></i> Remove</a>
                    </td>
                    <td class="cart-price">₹1179</td>
                </tr>
                <tr>
                    <td>
                        <div class="cart-item">
                            <div class="cart-item-img" style="background: linear-gradient(135deg, #9c27b0, #e91e63);"><i class="mdi mdi-account"></i></div>
                            <div class="cart-item-info">
                                <h4>Additional Main Artist</h4>
                                <p>1-year access unlimited releases to all Stores and Discovery Platforms under an additional Primary Artist name.</p>
                            </div>
                        </div>
                    </td>
                    <td class="cart-actions">
                        <a href="#" class="promo"><i class="mdi mdi-ticket-percent"></i> Apply Promo Code</a>
                        <a href="#" class="remove"><i class="mdi mdi-delete"></i> Remove</a>
                    </td>
                    <td class="cart-price">₹1179</td>
                </tr>
                <tr>
                    <td>
                        <div class="cart-item">
                            <div class="cart-item-img" style="background: linear-gradient(135deg, #9c27b0, #e91e63);"><i class="mdi mdi-account"></i></div>
                            <div class="cart-item-info">
                                <h4>Additional Main Artist</h4>
                                <p>1-year access unlimited releases to all Stores and Discovery Platforms under an additional Primary Artist name.</p>
                            </div>
                        </div>
                    </td>
                    <td class="cart-actions">
                        <a href="#" class="promo"><i class="mdi mdi-ticket-percent"></i> Apply Promo Code</a>
                        <a href="#" class="remove"><i class="mdi mdi-delete"></i> Remove</a>
                    </td>
                    <td class="cart-price">₹1179</td>
                </tr>
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>₹3537</span>
            </div>
            <div class="summary-row total">
                <span>*TOTAL</span>
                <span class="price">₹3536 <i class="mdi mdi-information-outline" style="font-size: 16px; color: rgba(255,255,255,0.5);"></i></span>
            </div>
            <button class="checkout-btn">
                <i class="mdi mdi-lock"></i>
                Continue to Payment
            </button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
