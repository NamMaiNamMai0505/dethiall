# 🚀 WSL Development Environment Setup Script

> Complete one-time setup script for a modern, productive development environment in WSL (Windows Subsystem for Linux)

## ✨ Features

### 🐚 Shell Enhancement
- **Zsh** - Modern shell with powerful features
- **Oh My Zsh** - Framework for managing Zsh configuration
- **Starship** - Fast, customizable, cross-platform prompt

### 🔌 Zsh Plugins
- **zsh-autosuggestions** - Fish-like autosuggestions
- **zsh-syntax-highlighting** - Real-time syntax highlighting
- **zsh-completions** - Additional completion definitions
- **zsh-history-substring-search** - Search history with arrow keys

### 🛠️ Modern CLI Tools
- **FZF** - Fuzzy finder for files and history (Ctrl+R, Ctrl+T)
- **bat** - Cat with syntax highlighting
- **eza** - Modern replacement for ls
- **fd** - Fast and user-friendly alternative to find
- **ripgrep (rg)** - Extremely fast grep alternative
- **tldr** - Simplified man pages
- **htop** - Interactive process viewer
- **ncdu** - Disk usage analyzer
- **tree** - Directory structure viewer
- **jq** - JSON processor

### 🎨 Additional Features
- **MesloLGS NF** - Nerd Font for icon support
- **Comprehensive aliases** - Git, Docker, Laravel shortcuts
- **Useful functions** - Custom helpers for development
- **Automatic backup** - Config files backed up before changes
- **Full logging** - Detailed log for troubleshooting

---

## 📥 Installation

### Quick Install (One Command)

```bash
curl -fsSL https://gist.githubusercontent.com/YOUR_USERNAME/YOUR_GIST_ID/raw/wsl-setup-complete.sh | bash
```

### Manual Install

```bash
# 1. Download the script
wget https://gist.githubusercontent.com/YOUR_USERNAME/YOUR_GIST_ID/raw/wsl-setup-complete.sh

# 2. Make it executable
chmod +x wsl-setup-complete.sh

# 3. Run the script
./wsl-setup-complete.sh
```

---

## 🎯 Post-Installation Steps

### 1. Change Default Shell

```bash
chsh -s $(which zsh)
```

### 2. Restart Terminal

Close and reopen your terminal, or run:

```bash
zsh
```

### 3. Configure Windows Terminal (Optional but Recommended)

1. Open Windows Terminal Settings (`Ctrl+,`)
2. Select your WSL profile
3. Set **Font Face** to: `MesloLGS NF`
4. Set **Font Size** to: `10` or `11`

---

## 🎓 Usage Guide

### Modern CLI Tools

```bash
# List files with icons and colors
ll

# View file with syntax highlighting
bat ~/.zshrc

# Find files (faster than find)
fd config

# Search in files (faster than grep)
rg "TODO"

# Interactive file finder
Ctrl+T

# Interactive history search
Ctrl+R

# Disk usage analyzer
ncdu

# Interactive process viewer
htop
```

### Git Aliases

```bash
gs          # git status
ga          # git add
gc          # git commit
gp          # git push
gl          # git log (pretty format)
gd          # git diff
gco         # git checkout
gb          # git branch

# Quick commit
gac "commit message"        # add + commit

# Quick push
gacp "commit message"       # add + commit + push
```

### Laravel Aliases

```bash
art         # php artisan
tinker      # php artisan tinker
serve       # php artisan serve
migrate     # php artisan migrate
mfs         # php artisan migrate:fresh --seed
seed        # php artisan db:seed
```

### Docker Aliases

```bash
dps         # docker ps
dpa         # docker ps -a
di          # docker images
dc          # docker-compose
dcu         # docker-compose up -d
dcd         # docker-compose down
dcl         # docker-compose logs -f
dcr         # docker-compose restart
```

### Useful Functions

```bash
# Show installed development tools versions
show_dev_versions

# Create directory and cd into it
mkcd my-new-project

# Extract any archive
extract archive.tar.gz

# Find in files with preview
fif "search term"
```

---

## 📁 File Locations

| File | Location |
|------|----------|
| Zsh Config | `~/.zshrc` |
| Starship Config | `~/.config/starship.toml` |
| Backup Directory | `~/.wsl-setup-backup-YYYYMMDD_HHMMSS/` |
| Log File | `~/wsl-setup-YYYYMMDD_HHMMSS.log` |

---

## ⚙️ Customization

### Edit Zsh Config

```bash
nano ~/.zshrc
# or
zshconfig
```

### Edit Starship Config

```bash
nano ~/.config/starship.toml
# or
starshipconfig
```

### Reload Config

```bash
source ~/.zshrc
# or
reload
```

### Change Starship Theme

```bash
# View available presets
starship preset

# Apply a preset (example)
starship preset nerd-font-symbols -o ~/.config/starship.toml
starship preset tokyo-night -o ~/.config/starship.toml
```

---

## 🎨 Starship Themes

The script includes a custom Starship configuration. You can try other presets:

```bash
# Minimalist
starship preset plain-text-symbols

# Nerd Font Symbols
starship preset nerd-font-symbols

# Tokyo Night
starship preset tokyo-night

# Pure
starship preset pure-preset
```

[View all presets](https://starship.rs/presets/)

---

## 🐛 Troubleshooting

### Script Failed?

1. Check the log file:
   ```bash
   cat ~/wsl-setup-*.log
   ```

2. Check backup directory:
   ```bash
   ls -la ~/.wsl-setup-backup-*/
   ```

3. Restore from backup if needed:
   ```bash
   cp ~/.wsl-setup-backup-*/.zshrc.backup ~/.zshrc
   ```

### Zsh Not Default Shell?

```bash
# Check current shell
echo $SHELL

# Change to Zsh
chsh -s $(which zsh)

# Restart terminal
```

### Icons Not Showing?

1. Install a Nerd Font in Windows Terminal
2. The script installs **MesloLGS NF** automatically
3. Configure Windows Terminal to use it

### FZF Not Working?

```bash
# Reinstall FZF
rm -rf ~/.fzf
git clone --depth 1 https://github.com/junegunn/fzf.git ~/.fzf
~/.fzf/install --all
```

---

## 🔄 Updating

### Update Oh My Zsh

```bash
omz update
```

### Update Starship

```bash
curl -sS https://starship.rs/install.sh | sh
```

### Update CLI Tools

```bash
sudo apt update && sudo apt upgrade -y
```

---

## 🗑️ Uninstallation

### Remove Oh My Zsh

```bash
uninstall_oh_my_zsh
```

### Remove Starship

```bash
rm ~/.local/bin/starship
rm ~/.config/starship.toml
```

### Restore Bash

```bash
chsh -s $(which bash)
```

---

## 📸 Screenshots

### Before
```
user@hostname:~$ ls
```

### After
```
╭─user on hostname in ~/projects [main ✓]
╰─➜ ll
```

With:
- 🎨 Colored output
- 📁 Icons for files/folders
- 🔀 Git branch and status
- ⚡ Fast and responsive
- 🔍 Fuzzy search everything

---

## 🤝 Contributing

Found a bug or have a suggestion? Feel free to:
- Open an issue
- Submit a pull request
- Comment on this gist

---

## 📝 Changelog

### Version 2.0 (Latest)
- ✅ Fixed `exa` → `eza` package name
- ✅ Added comprehensive error handling
- ✅ Implemented automatic backup system
- ✅ Added detailed logging
- ✅ Made script fully idempotent
- ✅ Added cleanup functionality
- ✅ Improved user feedback

### Version 1.0
- Initial release

---

## 📚 Resources

- [Oh My Zsh](https://ohmyz.sh/)
- [Starship](https://starship.rs/)
- [FZF](https://github.com/junegunn/fzf)
- [Zsh Plugins](https://github.com/zsh-users)
- [Nerd Fonts](https://www.nerdfonts.com/)

---

## ⭐ Star this Gist!

If this script helped you, please star ⭐ this gist and share it with others!

---

## 📄 License

MIT License - Feel free to use and modify as needed.

---

## 👨‍💻 Author

Created with ❤️ by **Claude + Tuyen Hoang**

For Laravel development and modern web development workflows.

---

## 💡 Tips

### Speed up your workflow:

1. **Use FZF everywhere**
   - `Ctrl+R` for command history
   - `Ctrl+T` for file search
   - `Alt+C` for directory navigation

2. **Leverage aliases**
   - Type `alias` to see all available aliases
   - Add your own in `~/.zshrc`

3. **Master Git shortcuts**
   - `gacp "message"` for add + commit + push
   - `gl` for beautiful git log

4. **Use modern tools**
   - `bat` instead of `cat`
   - `eza` instead of `ls`
   - `rg` instead of `grep`
   - `fd` instead of `find`

### Keyboard Shortcuts:

| Shortcut | Action |
|----------|--------|
| `Ctrl+R` | Search command history |
| `Ctrl+T` | Find files |
| `Alt+C` | Change directory |
| `Ctrl+L` | Clear screen |
| `!!` | Repeat last command |
| `sudo !!` | Repeat last command with sudo |

---

**Happy coding! 🚀**