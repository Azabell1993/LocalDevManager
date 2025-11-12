#include "loc_scanner.h"
#include <iostream>
#include <sstream>
#include <iomanip>
#include <string>

class ScanEngine {
private:
    LOCScanner scanner;
    
public:
    void runBatch(const std::string& projectPath) {
        try {
            ProjectScanResult result = scanner.scanProject(projectPath);
            outputResult(result);
        } catch (const std::exception& e) {
            std::cerr << "ERROR:" << e.what() << std::endl;
        }
    }
    
    void runInteractive() {
        std::string line;
        std::cout << "LOC Scanner Engine v" << scanner.getVersion() << " ready" << std::endl;
        
        while (std::getline(std::cin, line)) {
            if (line.empty() || line == "quit" || line == "exit") {
                break;
            }
            
            // 간단한 명령 파싱
            if (line.substr(0, 5) == "SCAN:") {
                std::string projectPath = line.substr(5);
                try {
                    ProjectScanResult result = scanner.scanProject(projectPath);
                    outputResult(result);
                } catch (const std::exception& e) {
                    std::cout << "ERROR:" << e.what() << std::endl;
                }
            } else if (line == "VERSION") {
                std::cout << "VERSION:" << scanner.getVersion() << std::endl;
            } else if (line == "PING") {
                std::cout << "PONG" << std::endl;
            } else {
                std::cout << "UNKNOWN_COMMAND:" << line << std::endl;
            }
            
            std::cout << std::flush;
        }
    }
    
private:
    void outputResult(const ProjectScanResult& result) {
        // 간단한 구분자 기반 출력 형식
        std::cout << "SCAN_RESULT_START" << std::endl;
        std::cout << "PROJECT_PATH:" << result.projectPath << std::endl;
        std::cout << "STATUS:" << result.status << std::endl;
        std::cout << "TOTAL_FILES:" << result.totalFiles << std::endl;
        std::cout << "TOTAL_LOC:" << result.totalLoc << std::endl;
        std::cout << "START_TIME:" << result.startTime << std::endl;
        std::cout << "END_TIME:" << result.endTime << std::endl;
        
        if (!result.errorMessage.empty()) {
            std::cout << "ERROR_MESSAGE:" << result.errorMessage << std::endl;
        }
        
        std::cout << "LANGUAGES_START" << std::endl;
        for (const auto& lang : result.languageResults) {
            std::cout << "LANG:" << lang.language 
                      << "|FILES:" << lang.fileCount
                      << "|TOTAL:" << lang.totalLines
                      << "|CODE:" << lang.codeLines
                      << "|COMMENTS:" << lang.commentLines
                      << "|BLANK:" << lang.blankLines << std::endl;
        }
        std::cout << "LANGUAGES_END" << std::endl;
        std::cout << "SCAN_RESULT_END" << std::endl;
    }
};

void printUsage() {
    std::cout << "LOC Scanner Engine v1.0.0" << std::endl;
    std::cout << "Usage:" << std::endl;
    std::cout << "  " << "loc_scanner_engine <project_path>  # Batch mode" << std::endl;
    std::cout << "  " << "loc_scanner_engine --interactive   # Interactive mode" << std::endl;
    std::cout << "  " << "loc_scanner_engine --version       # Show version" << std::endl;
    std::cout << "  " << "loc_scanner_engine --help          # Show this help" << std::endl;
}

int main(int argc, char* argv[]) {
    ScanEngine engine;
    
    if (argc == 1) {
        // No arguments - run interactive mode
        engine.runInteractive();
    } else if (argc == 2) {
        std::string arg = argv[1];
        
        if (arg == "--interactive" || arg == "-i") {
            engine.runInteractive();
        } else if (arg == "--version" || arg == "-v") {
            std::cout << "LOC Scanner Engine v1.0.0" << std::endl;
        } else if (arg == "--help" || arg == "-h") {
            printUsage();
        } else {
            // Treat as project path
            engine.runBatch(arg);
        }
    } else {
        std::cerr << "Error: Too many arguments" << std::endl;
        printUsage();
        return 1;
    }
    
    return 0;
}
