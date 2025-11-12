#ifndef LOC_SCANNER_H
#define LOC_SCANNER_H

#include <string>
#include <vector>
#include <map>
#include <filesystem>
#include <regex>

namespace fs = std::filesystem;

struct LanguageInfo {
    std::string name;
    std::vector<std::string> extensions;
    std::vector<std::string> singleLineComments;
    std::vector<std::pair<std::string, std::string>> multiLineComments;
};

struct ScanResult {
    std::string language;
    int fileCount = 0;
    int totalLines = 0;
    int codeLines = 0;
    int commentLines = 0;
    int blankLines = 0;
};

struct ProjectScanResult {
    std::string projectPath;
    std::string scanId;
    std::string status;
    int totalFiles = 0;
    int totalLoc = 0;
    std::vector<ScanResult> languageResults;
    std::string startTime;
    std::string endTime;
    std::string errorMessage;
};

class LOCScanner {
private:
    std::map<std::string, LanguageInfo> languages;
    std::vector<std::string> ignoredDirs;
    std::vector<std::string> ignoredFiles;
    
    void initializeLanguages();
    void initializeIgnorePatterns();
    std::string getLanguageByExtension(const std::string& extension);
    bool shouldIgnoreDirectory(const std::string& dirName);
    bool shouldIgnoreFile(const std::string& fileName);
    bool isBinaryFile(const std::string& filePath);
    ScanResult scanFile(const std::string& filePath, const std::string& language);
    int countLinesOfCode(const std::string& content, const LanguageInfo& langInfo);
    std::string removeComments(const std::string& content, const LanguageInfo& langInfo);
    std::string regexEscape(const std::string& str);
    std::string getCurrentTimestamp();
    std::string generateScanId();

public:
    LOCScanner();
    ProjectScanResult scanProject(const std::string& projectPath);
    std::string getVersion() const { return "1.0.0"; }
};

#endif // LOC_SCANNER_H