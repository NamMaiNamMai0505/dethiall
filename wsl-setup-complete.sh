#!/bin/bash

# =============================================================================
# WSL Development Environment Setup Script - Production Ready
# =============================================================================
# Author: Claude + Tuyen Hoang
# Version: 2.0
# Description: Complete one-time setup for Zsh, Oh My Zsh, Starship, and modern CLI tools
# Features: Idempotent, error handling, rollback capability, logging
# =============================================================================

set -euo pipefail  # Exit on error, undefined vars, pipe failures
IFS=$'\n\t'

# =============================================================================
# CONFIGURATION
# =============================================================================
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$HOME/wsl-setup-$(date +%Y%m%d_%H%M%S).log"
BACKUP_DIR="$HOME/.wsl-setup-backup-$(date +%Y%m%d_%H%M%S)"

# =============================================================================
# COLORS
# =============================================================================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# =============================================================================
# LOGGING FUNCTIONS
# =============================================================================
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

print_header() {
    echo -e "\n${PURPLE}========================================${NC}" | tee -a "$LOG_FILE"
    echo -e "${PURPLE}$1${NC}" | tee -a "$LOG_FILE"
    echo -e "${PURPLE}========================================${NC}\n" | tee -a "$LOG_FILE"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a "$LOG_FILE"
}

print_error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "$LOG_FILE"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}" | tee -a "$LOG_FILE"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}" | tee -a "$LOG_FILE"
}

# =============================================================================
# ERROR HANDLING
# =============================================================================
cleanup() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        print_error "Script failed with exit code $exit_code"
        print_info "Log file: $LOG_FILE"
        print_info "Backup directory: $BACKUP_DIR"
    fi
}

trap cleanup EXIT

# =============================================================================
# UTILITY FUNCTIONS
# =============================================================================
command_exists() {
    command -v "$1" &> /dev/null
}

backup_file() {
    local file=$1
    if [ -f "$file" ]; then
        mkdir -p "$BACKUP_DIR"
        cp "$file" "$BACKUP_DIR/$(basename "$file").backup"
        print_info "Backed up: $file"
    fi
}

install_if_missing() {
    local package=$1
    local check_command=${2:-$package}
    
    if command_exists "$check_command"; then
        print_info "$package already installed"
        return 0
    fi
    
    print_info "Installing $package..."
    if sudo apt install -y "$package" >> "$LOG_FILE" 2>&1; then
        print_success "$package installed"
        return 0
    else
        print_error "Failed to install $package"
        return 1
    fi
}

# =============================================================================
# MAIN SCRIPT
# =============================================================================
main() {
    log "Starting WSL setup script"
    print_header "🚀 WSL Development Environment Setup"
    
    # Create backup directory
    mkdir -p "$BACKUP_DIR"
    print_info "Backup directory: $BACKUP_DIR"
    print_info "Log file: $LOG_FILE"
    
    # =============================================================================
    # 1. SYSTEM UPDATE
    # =============================================================================
    print_header "📦 Updating System Packages"
    log "Updating package lists"
    
    if sudo apt update >> "$LOG_FILE" 2>&1; then
        print_success "Package lists updated"
    else
        print_error "Failed to update package lists"
        exit 1
    fi
    
    if sudo apt upgrade -y >> "$LOG_FILE" 2>&1; then
        print_success "System upgraded"
    else
        print_warning "System upgrade had issues (check log)"
    fi
    
    # =============================================================================
    # 2. INSTALL BASIC DEPENDENCIES
    # =============================================================================
    print_header "🔧 Installing Basic Dependencies"
    
    local basic_deps=(
        "curl"
        "wget"
        "git"
        "build-essential"
        "unzip"
        "software-properties-common"
        "apt-transport-https"
        "ca-certificates"
    )
    
    for dep in "${basic_deps[@]}"; do
        install_if_missing "$dep"
    done
    
    print_success "Basic dependencies installed"
    
    # =============================================================================
    # 3. INSTALL ZSH
    # =============================================================================
    print_header "🐚 Installing Zsh"
    install_if_missing "zsh"
    
    # =============================================================================
    # 4. INSTALL OH MY ZSH
    # =============================================================================
    print_header "🎨 Installing Oh My Zsh"
    
    if [ -d "$HOME/.oh-my-zsh" ]; then
        print_info "Oh My Zsh already installed"
    else
        print_info "Downloading Oh My Zsh..."
        if RUNZSH=no sh -c "$(curl -fsSL https://raw.githubusercontent.com/ohmyzsh/ohmyzsh/master/tools/install.sh)" "" --unattended >> "$LOG_FILE" 2>&1; then
            print_success "Oh My Zsh installed"
        else
            print_error "Failed to install Oh My Zsh"
            exit 1
        fi
    fi
    
    # =============================================================================
    # 5. INSTALL ZSH PLUGINS
    # =============================================================================
    print_header "🔌 Installing Zsh Plugins"
    
    local plugins=(
        "zsh-autosuggestions|https://github.com/zsh-users/zsh-autosuggestions"
        "zsh-syntax-highlighting|https://github.com/zsh-users/zsh-syntax-highlighting.git"
        "zsh-completions|https://github.com/zsh-users/zsh-completions"
        "zsh-history-substring-search|https://github.com/zsh-users/zsh-history-substring-search"
    )
    
    for plugin_info in "${plugins[@]}"; do
        IFS='|' read -r plugin_name plugin_url <<< "$plugin_info"
        local plugin_dir="${ZSH_CUSTOM:-$HOME/.oh-my-zsh/custom}/plugins/$plugin_name"
        
        if [ -d "$plugin_dir" ]; then
            print_info "$plugin_name already installed"
        else
            print_info "Installing $plugin_name..."
            if git clone "$plugin_url" "$plugin_dir" >> "$LOG_FILE" 2>&1; then
                print_success "$plugin_name installed"
            else
                print_error "Failed to install $plugin_name"
            fi
        fi
    done
    
    # =============================================================================
    # 6. INSTALL FZF
    # =============================================================================
    print_header "🔍 Installing FZF"
    
    if [ -d "$HOME/.fzf" ]; then
        print_info "FZF already installed"
    else
        print_info "Downloading FZF..."
        if git clone --depth 1 https://github.com/junegunn/fzf.git ~/.fzf >> "$LOG_FILE" 2>&1; then
            print_info "Installing FZF..."
            if ~/.fzf/install --all --no-bash --no-fish >> "$LOG_FILE" 2>&1; then
                print_success "FZF installed"
            else
                print_error "Failed to install FZF"
            fi
        else
            print_error "Failed to download FZF"
        fi
    fi
    
    # =============================================================================
    # 7. INSTALL MODERN CLI TOOLS
    # =============================================================================
    print_header "🛠️  Installing Modern CLI Tools"
    
    # bat (better cat)
    if ! command_exists batcat && ! command_exists bat; then
        install_if_missing "bat" "batcat"
        # Create symlink
        mkdir -p ~/.local/bin
        if [ -f /usr/bin/batcat ]; then
            ln -sf /usr/bin/batcat ~/.local/bin/bat
            print_success "Created bat symlink"
        fi
    else
        print_info "bat already installed"
    fi
    
    # eza (better ls)
    install_if_missing "eza"
    
    # fd (better find)
    if ! command_exists fd && ! command_exists fdfind; then
        install_if_missing "fd-find" "fdfind"
        # Create symlink
        mkdir -p ~/.local/bin
        if [ -f /usr/bin/fdfind ]; then
            ln -sf /usr/bin/fdfind ~/.local/bin/fd
            print_success "Created fd symlink"
        fi
    else
        print_info "fd already installed"
    fi
    
    # ripgrep (better grep)
    install_if_missing "ripgrep" "rg"
    
    # tldr (simplified man)
    install_if_missing "tldr"
    
    # htop (better top)
    install_if_missing "htop"
    
    # ncdu (disk usage)
    install_if_missing "ncdu"
    
    # tree
    install_if_missing "tree"
    
    # jq (JSON processor)
    install_if_missing "jq"
    
    print_success "All modern CLI tools installed"
    
    # =============================================================================
    # 8. INSTALL STARSHIP
    # =============================================================================
    print_header "🚀 Installing Starship Prompt"
    
    if command_exists starship; then
        print_info "Starship already installed"
    else
        print_info "Downloading and installing Starship..."
        if curl -sS https://starship.rs/install.sh | sh -s -- -y >> "$LOG_FILE" 2>&1; then
            print_success "Starship installed"
        else
            print_error "Failed to install Starship"
        fi
    fi
    
    # =============================================================================
    # 9. CREATE STARSHIP CONFIG
    # =============================================================================
    print_header "⚙️  Creating Starship Configuration"
    
    mkdir -p ~/.config
    backup_file "$HOME/.config/starship.toml"
    
    cat > ~/.config/starship.toml << 'STARSHIP_CONFIG'
# Starship Configuration
"$schema" = 'https://starship.rs/config-schema.json'
add_newline = true
format = """
[╭─](bold green)$username$hostname$directory$git_branch$git_status$nodejs$php$python$rust$golang$docker_context
[╰─](bold green)$character"""
command_timeout = 1000
[character]
success_symbol = "[➜](bold green)"
error_symbol = "[➜](bold red)"
[username]
style_user = "bold yellow"
style_root = "bold red"
format = "[$user]($style) "
disabled = false
show_always = true
[hostname]
ssh_only = false
format = "on [$hostname](bold cyan) "
disabled = false
[directory]
truncation_length = 3
truncate_to_repo = true
format = "in [$path]($style)[$read_only]($read_only_style) "
style = "bold blue"
[git_branch]
symbol = " "
format = "on [$symbol$branch]($style) "
style = "bold purple"
[git_status]
format = '([\[$all_status$ahead_behind\]]($style) )'
style = "bold red"
conflicted = "🏳"
ahead = "⇡${count}"
behind = "⇣${count}"
diverged = "⇕⇡${ahead_count}⇣${behind_count}"
up_to_date = "✓"
untracked = "?"
stashed = "$"
modified = "!"
staged = '[++\($count\)](green)'
renamed = "»"
deleted = "✘"
[nodejs]
symbol = " "
format = "via [$symbol($version )]($style)"
style = "bold green"
[php]
symbol = "🐘 "
format = "via [$symbol($version )]($style)"
style = "bold blue"
[python]
symbol = " "
format = "via [$symbol($version )]($style)"
style = "bold yellow"
[rust]
symbol = " "
format = "via [$symbol($version )]($style)"
style = "bold red"
[golang]
symbol = " "
format = "via [$symbol($version )]($style)"
style = "bold cyan"
[docker_context]
symbol = " "
format = "via [$symbol$context]($style) "
style = "bold blue"
[memory_usage]
disabled = true
[time]
disabled = false
format = '🕙[\[ $time \]]($style) '
time_format = "%T"
style = "bold white"
STARSHIP_CONFIG
    
    print_success "Starship configuration created"
    
    # =============================================================================
    # 10. CREATE .zshrc
    # =============================================================================
    print_header "📝 Creating .zshrc Configuration"
    
    backup_file "$HOME/.zshrc"
    
    cat > ~/.zshrc << 'ZSHRC_CONFIG'
# ===================================================================
# ZSH Configuration
# Created by WSL Setup Script
# ===================================================================
# Path to oh-my-zsh installation
export ZSH="$HOME/.oh-my-zsh"
# ===================================================================
# OH MY ZSH SETTINGS
# ===================================================================
ZSH_THEME=""
# Plugins
plugins=(
    git
    docker
    docker-compose
    npm
    node
    composer
    laravel
    sudo
    command-not-found
    colored-man-pages
    extract
    history
    web-search
    copypath
    copyfile
    copybuffer
    dirhistory
    jsontools
    zsh-autosuggestions
    zsh-syntax-highlighting
    zsh-completions
    zsh-history-substring-search
)
# Load Oh My Zsh
source $ZSH/oh-my-zsh.sh
# ===================================================================
# HISTORY SETTINGS
# ===================================================================
HISTSIZE=10000
SAVEHIST=10000
HISTFILE=~/.zsh_history
setopt SHARE_HISTORY
setopt HIST_IGNORE_ALL_DUPS
setopt HIST_IGNORE_SPACE
setopt HIST_REDUCE_BLANKS
setopt HIST_VERIFY
# ===================================================================
# PATH SETTINGS
# ===================================================================
export PATH="$HOME/.local/bin:$PATH"
# ===================================================================
# FZF SETTINGS
# ===================================================================
[ -f ~/.fzf.zsh ] && source ~/.fzf.zsh
export FZF_DEFAULT_OPTS='
--height 40%
--layout=reverse
--border
--inline-info
--preview "bat --style=numbers --color=always --line-range :500 {}"
--preview-window=right:60%:wrap
'
export FZF_DEFAULT_COMMAND='fd --type f --hidden --follow --exclude .git'
export FZF_CTRL_T_COMMAND="$FZF_DEFAULT_COMMAND"
# ===================================================================
# ALIASES
# ===================================================================
# Modern CLI tools
alias ls='eza --icons --group-directories-first'
alias ll='eza -la --icons --group-directories-first'
alias lt='eza -T --icons --level=2'
alias la='eza -a --icons --group-directories-first'
alias cat='bat'
alias find='fd'
alias grep='rg'
# Git aliases
alias gs='git status'
alias ga='git add'
alias gc='git commit'
alias gp='git push'
alias gl='git log --oneline --graph --decorate'
alias gd='git diff'
alias gco='git checkout'
alias gb='git branch'
# Laravel aliases
alias art='php artisan'
alias tinker='php artisan tinker'
alias serve='php artisan serve'
alias migrate='php artisan migrate'
alias mfs='php artisan migrate:fresh --seed'
alias seed='php artisan db:seed'
# Docker aliases
alias dps='docker ps'
alias dpa='docker ps -a'
alias di='docker images'
alias dc='docker-compose'
alias dcu='docker-compose up -d'
alias dcd='docker-compose down'
alias dcl='docker-compose logs -f'
alias dcr='docker-compose restart'
# Directory navigation
alias ..='cd ..'
alias ...='cd ../..'
alias ....='cd ../../..'
alias ~='cd ~'
# Utility aliases
alias h='history'
alias c='clear'
alias reload='source ~/.zshrc'
alias zshconfig='nano ~/.zshrc'
alias starshipconfig='nano ~/.config/starship.toml'
# System aliases
alias update='sudo apt update && sudo apt upgrade -y'
alias install='sudo apt install'
alias remove='sudo apt remove'
alias cleanup='sudo apt autoremove -y && sudo apt autoclean'
# ===================================================================
# FUNCTIONS
# ===================================================================
# Show development versions
show_dev_versions() {
    echo ""
    if command -v node &> /dev/null; then
        echo "🟩 Node.js: $(node -v)   🟦 npm: $(npm -v)"
    fi
    if command -v php &> /dev/null; then
        echo "🐘 PHP: $(php -v | head -n1 | cut -d' ' -f2)"
    fi
    if command -v python3 &> /dev/null; then
        echo "🐍 Python: $(python3 --version | cut -d' ' -f2)"
    fi
    if command -v docker &> /dev/null; then
        echo "🐳 Docker: $(docker --version | cut -d' ' -f3 | tr -d ',')"
    fi
    if command -v composer &> /dev/null; then
        echo "🎵 Composer: $(composer --version | cut -d' ' -f3)"
    fi
    echo ""
}
# Create directory and cd into it
mkcd() {
    mkdir -p "$1" && cd "$1"
}
# Extract any archive
extract() {
    if [ -f $1 ]; then
        case $1 in
            *.tar.bz2)   tar xjf $1     ;;
            *.tar.gz)    tar xzf $1     ;;
            *.bz2)       bunzip2 $1     ;;
            *.rar)       unrar e $1     ;;
            *.gz)        gunzip $1      ;;
            *.tar)       tar xf $1      ;;
            *.tbz2)      tar xjf $1     ;;
            *.tgz)       tar xzf $1     ;;
            *.zip)       unzip $1       ;;
            *.Z)         uncompress $1  ;;
            *.7z)        7z x $1        ;;
            *)           echo "'$1' cannot be extracted" ;;
        esac
    else
        echo "'$1' is not a valid file"
    fi
}
# Find in files using ripgrep and fzf
fif() {
    rg --files-with-matches --no-messages "$1" | fzf --preview "bat --color=always {} | rg --colors 'match:bg:yellow' --ignore-case --pretty --context 10 '$1' || rg --ignore-case --pretty --context 10 '$1' {}"
}
# Git quick commit
gac() {
    git add .
    git commit -m "$1"
}
# Git quick push
gacp() {
    git add .
    git commit -m "$1"
    git push
}
# ===================================================================
# STARSHIP PROMPT
# ===================================================================
eval "$(starship init zsh)"
# ===================================================================
# WELCOME MESSAGE
# ===================================================================
show_dev_versions
echo "✨ Zsh environment loaded successfully!"
ZSHRC_CONFIG
    
    print_success ".zshrc configuration created"
    
    # =============================================================================
    # 11. INSTALL NERD FONTS
    # =============================================================================
    print_header "🎨 Installing Nerd Fonts"
    
    FONT_DIR="$HOME/.local/share/fonts"
    mkdir -p "$FONT_DIR"
    
    local fonts=(
        "MesloLGS%20NF%20Regular.ttf"
        "MesloLGS%20NF%20Bold.ttf"
        "MesloLGS%20NF%20Italic.ttf"
        "MesloLGS%20NF%20Bold%20Italic.ttf"
    )
    
    cd "$FONT_DIR"
    for font in "${fonts[@]}"; do
        if [ ! -f "$FONT_DIR/$font" ]; then
            print_info "Downloading $font..."
            if wget -q "https://github.com/romkatv/powerlevel10k-media/raw/master/$font" >> "$LOG_FILE" 2>&1; then
                print_success "Downloaded $font"
            else
                print_warning "Failed to download $font"
            fi
        else
            print_info "$font already exists"
        fi
    done
    
    # Update font cache
    if fc-cache -f "$FONT_DIR" >> "$LOG_FILE" 2>&1; then
        print_success "Font cache updated"
    else
        print_warning "Failed to update font cache"
    fi
    
    # =============================================================================
    # 12. CLEANUP
    # =============================================================================
    print_header "🧹 Cleaning Up"
    
    if sudo apt autoremove -y >> "$LOG_FILE" 2>&1; then
        print_success "Removed unnecessary packages"
    fi
    
    if sudo apt autoclean >> "$LOG_FILE" 2>&1; then
        print_success "Cleaned package cache"
    fi
    
    # =============================================================================
    # FINAL SUMMARY
    # =============================================================================
    print_header "🎉 Installation Complete!"
    
    echo ""
    print_success "All components installed successfully!"
    echo ""
    print_info "Installed components:"
    echo "  ✓ Zsh shell"
    echo "  ✓ Oh My Zsh framework"
    echo "  ✓ Starship prompt"
    echo "  ✓ 4 Zsh plugins (autosuggestions, syntax-highlighting, completions, history-search)"
    echo "  ✓ FZF fuzzy finder"
    echo "  ✓ Modern CLI tools (bat, eza, fd, ripgrep, tldr, htop, ncdu, tree, jq)"
    echo "  ✓ MesloLGS NF fonts"
    echo ""
    print_warning "NEXT STEPS:"
    echo ""
    echo "  ${CYAN}1. Change your default shell to Zsh:${NC}"
    echo "     ${YELLOW}chsh -s \$(which zsh)${NC}"
    echo ""
    echo "  ${CYAN}2. Close and reopen your terminal, or run:${NC}"
    echo "     ${YELLOW}zsh${NC}"
    echo ""
    echo "  ${CYAN}3. (Optional) Configure Windows Terminal:${NC}"
    echo "     • Open Settings (Ctrl+,)"
    echo "     • Select your WSL profile"
    echo "     • Set Font Face to: ${YELLOW}MesloLGS NF${NC}"
    echo "     • Set Font Size to: ${YELLOW}10 or 11${NC}"
    echo ""
    print_info "Useful commands to try:"
    echo "  ${CYAN}ll${NC}                    - List files with icons"
    echo "  ${CYAN}Ctrl+R${NC}                - Search command history with FZF"
    echo "  ${CYAN}Ctrl+T${NC}                - Find files with FZF"
    echo "  ${CYAN}cat file.txt${NC}          - View file with syntax highlighting"
    echo "  ${CYAN}show_dev_versions${NC}     - Show installed dev tools versions"
    echo ""
    print_info "Configuration files:"
    echo "  • Zsh config: ${CYAN}~/.zshrc${NC}"
    echo "  • Starship config: ${CYAN}~/.config/starship.toml${NC}"
    echo "  • Backup directory: ${CYAN}$BACKUP_DIR${NC}"
    echo "  • Log file: ${CYAN}$LOG_FILE${NC}"
    echo ""
    print_success "Enjoy your supercharged terminal! 🚀"
    echo ""
}

# =============================================================================
# RUN MAIN FUNCTION
# =============================================================================
main "$@"