/**
 * Anti-Cheating System for ExamPro
 * This file contains various anti-cheating measures for online exams
 */

class AntiCheatingSystem {
  constructor(options = {}) {
    // Configuration defaults
    this.config = {
      preventCopyPaste: true,
      preventTabSwitching: true,
      preventRightClick: true,
      preventPrintScreen: true,
      fullscreenMode: true,
      logSuspiciousActivity: true,
      shuffleQuestions: options.shuffleQuestions || false,
      shuffleOptions: options.shuffleOptions || false,
      examId: options.examId || null,
      userId: options.userId || null,
      logEndpoint: options.logEndpoint || "log_suspicious_activity.php",
      warningThreshold: options.warningThreshold || 3,
      maxWarnings: options.maxWarnings || 5,
      ...options,
    };

    // State variables
    this.warningCount = 0;
    this.suspiciousActivities = [];
    this.fullscreenActive = false;
    this.sessionStartTime = new Date();
    this.lastActive = new Date();
    this.tabFocused = true;

    // Initialize
    this.init();
  }

  init() {
    // Apply all anti-cheating measures
    if (this.config.preventCopyPaste) this.preventCopyPaste();
    if (this.config.preventTabSwitching) this.preventTabSwitching();
    if (this.config.preventRightClick) this.preventRightClick();
    if (this.config.preventPrintScreen) this.preventPrintScreen();
    if (this.config.fullscreenMode) this.enableFullscreen();

    // Set up periodic checks
    this.setupPeriodicChecks();

    // Add beforeunload event
    this.preventPageLeaving();

    // Log initial session
    this.logActivity("session_start");

    // Setup unload handler
    window.addEventListener("unload", () => {
      this.logActivity("session_end");
    });

    console.log("Anti-cheating system initialized");
  }

  preventCopyPaste() {
    document.addEventListener("copy", (e) => {
      this.warnUser("Copier du texte n'est pas autorisé pendant l'examen");
      this.logActivity("copy_attempt");
      e.preventDefault();
    });

    document.addEventListener("cut", (e) => {
      this.warnUser("Couper du texte n'est pas autorisé pendant l'examen");
      this.logActivity("cut_attempt");
      e.preventDefault();
    });

    document.addEventListener("paste", (e) => {
      this.warnUser("Coller du texte n'est pas autorisé pendant l'examen");
      this.logActivity("paste_attempt");
      e.preventDefault();
    });
  }

  preventTabSwitching() {
    document.addEventListener("visibilitychange", () => {
      if (document.hidden) {
        this.tabFocused = false;
        this.warnUser(
          "Quitter cette page pendant l'examen est considéré comme une tentative de triche"
        );
        this.logActivity("tab_switch");
      } else {
        this.tabFocused = true;
        // User returned to the exam
        this.logActivity("returned_to_exam");
      }
    });
  }

  preventRightClick() {
    document.addEventListener("contextmenu", (e) => {
      this.warnUser("Le menu contextuel est désactivé pendant l'examen");
      this.logActivity("right_click_attempt");
      e.preventDefault();
    });
  }

  preventPrintScreen() {
    // Monitor key combinations
    document.addEventListener("keydown", (e) => {
      // Print Screen key
      if (e.key === "PrintScreen") {
        this.warnUser(
          "Les captures d'écran ne sont pas autorisées pendant l'examen"
        );
        this.logActivity("print_screen_attempt");
        e.preventDefault();
        return false;
      }

      // Ctrl+P (Print)
      if (e.ctrlKey && e.key === "p") {
        this.warnUser("L'impression n'est pas autorisée pendant l'examen");
        this.logActivity("print_attempt");
        e.preventDefault();
        return false;
      }

      // Alt+Tab detection (not perfect but can help)
      if (e.altKey && e.key === "Tab") {
        this.warnUser("Changer de fenêtre n'est pas autorisé pendant l'examen");
        this.logActivity("alt_tab_detected");
        e.preventDefault();
        return false;
      }

      // Function keys (F12 for dev tools)
      if (e.key === "F12") {
        this.warnUser(
          "L'utilisation des outils de développeur n'est pas autorisée"
        );
        this.logActivity("dev_tools_attempt");
        e.preventDefault();
        return false;
      }
    });
  }

  enableFullscreen() {
    // Add fullscreen button
    const fullscreenBtn = document.createElement("button");
    fullscreenBtn.id = "fullscreen-btn";
    fullscreenBtn.innerText = "Passer en plein écran";
    fullscreenBtn.className = "btn btn-warning";
    fullscreenBtn.style.position = "fixed";
    fullscreenBtn.style.top = "10px";
    fullscreenBtn.style.right = "10px";
    fullscreenBtn.style.zIndex = "9999";

    fullscreenBtn.addEventListener("click", () => {
      this.toggleFullscreen();
    });

    document.body.appendChild(fullscreenBtn);

    // Monitor fullscreen changes
    document.addEventListener("fullscreenchange", () => {
      this.fullscreenActive = !!document.fullscreenElement;

      if (!this.fullscreenActive && this.config.fullscreenMode) {
        this.warnUser(
          "Quitter le mode plein écran n'est pas recommandé pendant l'examen"
        );
        this.logActivity("exit_fullscreen");

        // Update button text
        const btn = document.getElementById("fullscreen-btn");
        if (btn) btn.innerText = "Passer en plein écran";
      } else {
        // Update button text
        const btn = document.getElementById("fullscreen-btn");
        if (btn) btn.innerText = "Quitter le plein écran";
      }
    });
  }

  toggleFullscreen() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch((err) => {
        this.warnUser(`Erreur lors du passage en plein écran: ${err.message}`);
      });
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      }
    }
  }

  setupPeriodicChecks() {
    // Check every 10 seconds
    setInterval(() => {
      this.checkIdleTime();
      this.checkWindowSize();
    }, 10000);
  }

  checkIdleTime() {
    const now = new Date();
    const idleTime = now - this.lastActive;

    // If idle for more than 1 minute (60000 ms)
    if (idleTime > 60000 && this.tabFocused) {
      this.logActivity("idle_detected", { idleTime });
    }

    // Update timestamp on user interaction
    document.addEventListener(
      "mousemove",
      () => (this.lastActive = new Date())
    );
    document.addEventListener("keydown", () => (this.lastActive = new Date()));
    document.addEventListener("click", () => (this.lastActive = new Date()));
  }

  checkWindowSize() {
    // Check if window has been resized to a very small size (possible attempt to hide in corner)
    if (window.innerWidth < 800 || window.innerHeight < 600) {
      this.warnUser(
        "Redimensionner la fenêtre à une taille trop petite n'est pas autorisé"
      );
      this.logActivity("suspicious_resize", {
        width: window.innerWidth,
        height: window.innerHeight,
      });
    }
  }

  preventPageLeaving() {
    window.addEventListener("beforeunload", (e) => {
      // This will show a confirmation dialog before leaving the page
      e.preventDefault();
      e.returnValue = "";

      this.logActivity("attempted_page_exit");
      return "";
    });
  }

  shuffleQuestions() {
    // Implementation depends on your DOM structure
    const questionsContainer = document.querySelector("#questionsContainer");
    if (!questionsContainer) return;

    const questions = Array.from(questionsContainer.children);

    // Fisher-Yates shuffle algorithm
    for (let i = questions.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [questions[i], questions[j]] = [questions[j], questions[i]];
    }

    // Clear and re-append in new order
    questionsContainer.innerHTML = "";
    questions.forEach((question) => questionsContainer.appendChild(question));

    // Update question numbers if needed
    const questionNumbers = document.querySelectorAll(".question-number");
    questionNumbers.forEach((numEl, index) => {
      numEl.textContent = `Question ${index + 1} sur ${questions.length}`;
    });
  }

  shuffleOptions(questionId) {
    // Implementation depends on your DOM structure
    const optionsContainer = document.querySelector(
      `#question-${questionId} .mcq-options`
    );
    if (!optionsContainer) return;

    const options = Array.from(optionsContainer.children);

    // Fisher-Yates shuffle algorithm
    for (let i = options.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [options[i], options[j]] = [options[j], options[i]];
    }

    // Clear and re-append in new order
    optionsContainer.innerHTML = "";
    options.forEach((option) => optionsContainer.appendChild(option));
  }

  warnUser(message) {
    this.warningCount++;

    // Create warning element
    const warningElement = document.createElement("div");
    warningElement.className = "anti-cheat-warning";
    warningElement.innerHTML = `
            <div class="warning-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="warning-message">${message}</div>
            <div class="warning-count">Avertissement ${this.warningCount}/${this.config.maxWarnings}</div>
        `;

    document.body.appendChild(warningElement);

    // Show the warning
    setTimeout(() => {
      warningElement.classList.add("show");
    }, 100);

    // Remove after 5 seconds
    setTimeout(() => {
      warningElement.classList.remove("show");
      setTimeout(() => warningElement.remove(), 500);
    }, 5000);

    // Auto-submit if max warnings exceeded
    if (this.warningCount >= this.config.maxWarnings) {
      this.logActivity("max_warnings_exceeded");
      alert(
        "Trop d'activités suspectes détectées. Votre examen va être soumis automatiquement."
      );

      // Submit form
      const examForm = document.getElementById("examForm");
      if (examForm) {
        examForm.submit();
      }
    }
  }

  logActivity(activityType, details = {}) {
    if (!this.config.logSuspiciousActivity) return;

    const activity = {
      type: activityType,
      timestamp: new Date().toISOString(),
      examId: this.config.examId,
      userId: this.config.userId,
      userAgent: navigator.userAgent,
      ...details,
    };

    this.suspiciousActivities.push(activity);

    // Log to console for debugging
    console.log(`Activity logged: ${activityType}`, activity);

    // Send to server
    fetch(this.config.logEndpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(activity),
      // Add credentials to ensure cookies are sent
      credentials: "same-origin",
    })
      .then((response) => {
        if (!response.ok) {
          console.error(
            "Error logging activity: Server returned",
            response.status
          );
          return response.text().then((text) => {
            console.error("Response:", text);
          });
        }
        return response.json();
      })
      .then((data) => {
        // Log success for debugging
        if (data && data.status === "success") {
          console.log("Activity logged successfully on server");
        }
      })
      .catch((err) => console.error("Error logging activity:", err));
  }
}

// CSS styles for warning message
const style = document.createElement("style");
style.textContent = `
.anti-cheat-warning {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(-100px);
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 15px 20px;
    border-radius: 5px;
    z-index: 10000;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    transition: all 0.5s ease;
    max-width: 90%;
}

.anti-cheat-warning.show {
    transform: translateX(-50%) translateY(0);
}

.warning-icon {
    margin-right: 15px;
    font-size: 24px;
}

.warning-message {
    flex-grow: 1;
    font-weight: 600;
}

.warning-count {
    margin-left: 15px;
    background: #dc3545;
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 12px;
}
`;
document.head.appendChild(style);
