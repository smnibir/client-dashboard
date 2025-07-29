<?php
if (preg_match('/\\((https?:\\/\\/reports\\.webgrowth\\.io\\/[^\\s]+)\\)/', $content, $match)) {
    $iframe_url = $match[1];
} elseif (preg_match('/\\((https?:\\/\\/forms\\.clickup\\.com\\/[^\\s]+)\\)/', $content, $match)) {
    $iframe_url = $match[1];
}
?>

<?php if ($iframe_url): ?>
<div class="common-padding">
    <div class="tab-head" style="z-index: 9999;">
    <h2>Support Center</h2>
    <span>Get help with any questions or issues you're experiencing</span>
    
</div>
    <div class="flex" style="z-index: 9;">
        <iframe src="<?php echo esc_url($iframe_url); ?>" class="clickup-iframe" style="height:100vh;width: 100vw;margin-right: -24px;" frameborder="0" rel="preload"></iframe>
        <div>
            <div class="support-container">
              <div class="response-times">
                <h3>Response Times</h3>
                <ul>
                  <li><span class="dot low"></span> <div class="priority-status"><span>Low Priority</span> <span>24–48 hours</span></div></li>
                  <li><span class="dot medium"></span> <div class="priority-status"><span>Medium Priority</span> <span>4–24 hours</span></div></li>
                  <li><span class="dot urgent"></span><div class="priority-status"> <span>Urgent</span> <span>1–4 hours</span></div></li>
                </ul>
              </div>
            
              <div class="immediate-help">
                <h3>Need Immediate Help?</h3>
                <p style="color:#999;margin-bottom: 0.9rem;">For urgent issues, contact your account manager directly:</p>
                <p class="manager-name" style="font-size: 0.975rem;margin-bottom: 0.6em;">Webgrowth Support Bot</p>
                <p class="email" style="margin-bottom: 0em;">support@webgrowth.io</p>
                <p class="phone">480.331.5849</p>
               <a href="tel:480.331.5849" class="quick-call">
  <span class="icon">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
      stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap">
      <path
        d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
      </path>
    </svg>
  </span>
  Quick Call
</a>

              </div>
            </div>
        </div>
    </div>
</div>
    <style>

.support-container {
  min-width: 500px;
  margin:  auto;
  /*padding: 20px;*/
  border-radius: 10px;
  height: 100vh;
  background: #111111;
}

.response-times,
.immediate-help {
  background: #161616;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  border: 1px solid #2e2e2e;
}

.response-times h3,
.immediate-help h3 {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 18px;
  font-weight: 600;
}

.response-times ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.response-times li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 15px;
}

.dot {
  height: 10px;
  width: 10px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 8px;
}

.low {
  background-color: #00c853;
}

.medium {
  background-color: #ffab00;
}

.urgent {
  background-color: #d50000;
}

.manager-name {
  font-weight: bold;
}

.email,
.phone {

  font-size: 0.75rem;
  color: #999;
}

.quick-call {
  margin-top: 15px;
  width: 100%;
  padding: 10px;
  background-color: #1f1f1f;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: background 0.3s;
}

.quick-call:hover {
  color: #44da67;
}

.icon {
  margin-right: 8px;
  font-size: 16px;
}

    </style>
<?php else: ?>
    <p>No valid report URL found in content.</p>
<?php endif; ?>
