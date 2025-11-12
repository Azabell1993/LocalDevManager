#include "loc_scanner.h"
#include <iostream>
#include <fstream>
#include <sstream>
#include <chrono>
#include <random>
#include <algorithm>
#include <cctype>

LOCScanner::LOCScanner() {
    initializeLanguages();
    initializeIgnorePatterns();
}

void LOCScanner::initializeLanguages() {
    // C/C++
    languages["c"] = {
        "C", {"c", "h"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    languages["cpp"] = {
        "C++", {"cpp", "cc", "cxx", "hpp", "hxx"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    // Java
    languages["java"] = {
        "Java", {"java"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    // Python
    languages["python"] = {
        "Python", {"py", "pyw"}, 
        {"#"},
        {{"\"\"\"", "\"\"\""}, {"'''", "'''"}}
    };
    
    // PHP
    languages["php"] = {
        "PHP", {"php", "phtml"}, 
        {"//", "#"},
        {{"/*", "*/"}}
    };
    
    // JavaScript/TypeScript
    languages["javascript"] = {
        "JavaScript", {"js", "jsx"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    languages["typescript"] = {
        "TypeScript", {"ts", "tsx"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    // C#
    languages["csharp"] = {
        "C#", {"cs"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    // Go
    languages["go"] = {
        "Go", {"go"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    // Rust
    languages["rust"] = {
        "Rust", {"rs"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    // Ruby
    languages["ruby"] = {
        "Ruby", {"rb", "rbw"}, 
        {"#"},
        {{"=begin", "=end"}}
    };
    
    // Swift
    languages["swift"] = {
        "Swift", {"swift"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    // Kotlin
    languages["kotlin"] = {
        "Kotlin", {"kt", "kts"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    // Shell
    languages["shell"] = {
        "Shell", {"sh", "bash", "zsh"}, 
        {"#"},
        {}
    };
    
    // PowerShell
    languages["powershell"] = {
        "PowerShell", {"ps1"}, 
        {"#"},
        {{"<#", "#>"}}
    };
    
    // R
    languages["r"] = {
        "R", {"r", "R"}, 
        {"#"},
        {}
    };
    
    // MATLAB
    languages["matlab"] = {
        "MATLAB", {"m"}, 
        {"%"},
        {{"%%", "%%"}}
    };
    
    // Lua
    languages["lua"] = {
        "Lua", {"lua"}, 
        {"--"},
        {{"--[[", "]]"}}
    };
    
    // Haskell
    languages["haskell"] = {
        "Haskell", {"hs"}, 
        {"--"},
        {{"{-", "-}"}}
    };
    
    // Scala
    languages["scala"] = {
        "Scala", {"scala"}, 
        {"//"},
        {{"/*", "*/"}}
    };
    
    // Perl
    languages["perl"] = {
        "Perl", {"pl", "pm"}, 
        {"#"},
        {{"=pod", "=cut"}}
    };
}

void LOCScanner::initializeIgnorePatterns() {
    ignoredDirs = {
        "node_modules", "vendor", "build", "dist", "out", "target",
        ".git", ".svn", ".hg", ".bzr",
        "__pycache__", ".cache", ".pytest_cache",
        "bin", "obj", ".vs", ".vscode",
        "coverage", ".nyc_output", ".tmp", "temp"
    };
    
    ignoredFiles = {
        ".gitignore", ".gitkeep", ".DS_Store", "Thumbs.db",
        "package-lock.json", "yarn.lock", "composer.lock"
    };
}

std::string LOCScanner::getLanguageByExtension(const std::string& extension) {
    std::string lowerExt = extension;
    std::transform(lowerExt.begin(), lowerExt.end(), lowerExt.begin(), ::tolower);
    
    for (const auto& [key, lang] : languages) {
        for (const auto& ext : lang.extensions) {
            if (ext == lowerExt) {
                return key;
            }
        }
    }
    return "";
}

bool LOCScanner::shouldIgnoreDirectory(const std::string& dirName) {
    return std::find(ignoredDirs.begin(), ignoredDirs.end(), dirName) != ignoredDirs.end();
}

bool LOCScanner::shouldIgnoreFile(const std::string& fileName) {
    return std::find(ignoredFiles.begin(), ignoredFiles.end(), fileName) != ignoredFiles.end();
}

bool LOCScanner::isBinaryFile(const std::string& filePath) {
    std::ifstream file(filePath, std::ios::binary);
    if (!file) return true;
    
    char buffer[512];
    file.read(buffer, sizeof(buffer));
    size_t bytesRead = file.gcount();
    
    for (size_t i = 0; i < bytesRead; ++i) {
        if (buffer[i] == 0) return true; // NULL byte indicates binary
    }
    return false;
}

std::string LOCScanner::regexEscape(const std::string& str) {
    static const std::regex specialChars { R"([-[\]{}()*+?.,\^$|#\s])" };
    return std::regex_replace(str, specialChars, R"(\$&)");
}

std::string LOCScanner::removeComments(const std::string& content, const LanguageInfo& langInfo) {
    std::string result = content;
    
    // Remove multi-line comments first
    for (const auto& [start, end] : langInfo.multiLineComments) {
        std::regex multiLineRegex(regexEscape(start) + ".*?" + regexEscape(end));
        result = std::regex_replace(result, multiLineRegex, "", std::regex_constants::match_default);
    }
    
    // Remove single-line comments
    std::istringstream iss(result);
    std::ostringstream oss;
    std::string line;
    
    while (std::getline(iss, line)) {
        for (const auto& comment : langInfo.singleLineComments) {
            size_t pos = line.find(comment);
            if (pos != std::string::npos) {
                line = line.substr(0, pos);
                break;
            }
        }
        oss << line << "\n";
    }
    
    return oss.str();
}

int LOCScanner::countLinesOfCode(const std::string& content, const LanguageInfo& langInfo) {
    std::string cleanContent = removeComments(content, langInfo);
    
    std::istringstream iss(cleanContent);
    std::string line;
    int loc = 0;
    
    while (std::getline(iss, line)) {
        // Trim whitespace
        line.erase(0, line.find_first_not_of(" \t\r\n"));
        line.erase(line.find_last_not_of(" \t\r\n") + 1);
        
        if (!line.empty()) {
            loc++;
        }
    }
    
    return loc;
}

ScanResult LOCScanner::scanFile(const std::string& filePath, const std::string& language) {
    ScanResult result;
    result.language = languages[language].name;
    result.fileCount = 1;
    
    std::ifstream file(filePath);
    if (!file) {
        return result;
    }
    
    std::string content((std::istreambuf_iterator<char>(file)),
                        std::istreambuf_iterator<char>());
    
    // Count total lines
    result.totalLines = std::count(content.begin(), content.end(), '\n') + 1;
    
    // Count code lines
    result.codeLines = countLinesOfCode(content, languages[language]);
    
    // Count blank lines and comment lines (simplified)
    std::istringstream iss(content);
    std::string line;
    while (std::getline(iss, line)) {
        line.erase(0, line.find_first_not_of(" \t\r"));
        line.erase(line.find_last_not_of(" \t\r") + 1);
        
        if (line.empty()) {
            result.blankLines++;
        }
    }
    
    result.commentLines = result.totalLines - result.codeLines - result.blankLines;
    
    return result;
}

std::string LOCScanner::getCurrentTimestamp() {
    auto now = std::chrono::system_clock::now();
    auto time_t = std::chrono::system_clock::to_time_t(now);
    
    std::ostringstream oss;
    oss << std::put_time(std::localtime(&time_t), "%Y-%m-%d %H:%M:%S");
    return oss.str();
}

std::string LOCScanner::generateScanId() {
    std::random_device rd;
    std::mt19937 gen(rd());
    std::uniform_int_distribution<> dis(100000, 999999);
    
    return "scan_" + std::to_string(dis(gen));
}

ProjectScanResult LOCScanner::scanProject(const std::string& projectPath) {
    ProjectScanResult result;
    result.projectPath = projectPath;
    result.scanId = generateScanId();
    result.startTime = getCurrentTimestamp();
    result.status = "running";
    
    try {
        std::map<std::string, ScanResult> langResults;
        
        for (const auto& entry : fs::recursive_directory_iterator(projectPath)) {
            if (entry.is_directory()) {
                if (shouldIgnoreDirectory(entry.path().filename().string())) {
                    continue;
                }
            } else if (entry.is_regular_file()) {
                std::string fileName = entry.path().filename().string();
                if (shouldIgnoreFile(fileName)) {
                    continue;
                }
                
                if (isBinaryFile(entry.path().string())) {
                    continue;
                }
                
                std::string extension = entry.path().extension().string();
                if (!extension.empty() && extension[0] == '.') {
                    extension = extension.substr(1);
                }
                
                std::string language = getLanguageByExtension(extension);
                if (!language.empty()) {
                    ScanResult fileResult = scanFile(entry.path().string(), language);
                    
                    if (langResults.find(language) == langResults.end()) {
                        langResults[language] = fileResult;
                    } else {
                        langResults[language].fileCount += fileResult.fileCount;
                        langResults[language].totalLines += fileResult.totalLines;
                        langResults[language].codeLines += fileResult.codeLines;
                        langResults[language].commentLines += fileResult.commentLines;
                        langResults[language].blankLines += fileResult.blankLines;
                    }
                    
                    result.totalFiles++;
                    result.totalLoc += fileResult.codeLines;
                }
            }
        }
        
        for (const auto& [lang, scanResult] : langResults) {
            result.languageResults.push_back(scanResult);
        }
        
        result.status = "success";
        
    } catch (const std::exception& e) {
        result.status = "failed";
        result.errorMessage = e.what();
    }
    
    result.endTime = getCurrentTimestamp();
    return result;
}