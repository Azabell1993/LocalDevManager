CXX = g++
CXXFLAGS = -std=c++17 -Wall -Wextra -O2
INCLUDES = -I/usr/local/include
LIBS = 
SRCDIR = .
BUILDDIR = build
TARGET = $(BUILDDIR)/loc_scanner_engine

# Source files
SOURCES = $(wildcard $(SRCDIR)/*.cpp)
OBJECTS = $(SOURCES:$(SRCDIR)/%.cpp=$(BUILDDIR)/%.o)

# Default target
all: $(TARGET)

# Create build directory
$(BUILDDIR):
	mkdir -p $(BUILDDIR)

# Link executable
$(TARGET): $(OBJECTS) | $(BUILDDIR)
	$(CXX) $(OBJECTS) -o $@ $(LIBS)
	@echo "Built C++ LOC Scanner Engine: $(TARGET)"

# Compile source files
$(BUILDDIR)/%.o: $(SRCDIR)/%.cpp | $(BUILDDIR)
	$(CXX) $(CXXFLAGS) $(INCLUDES) -c $< -o $@

# Install jsoncpp if needed (macOS with Homebrew)
install-deps:
	@echo "Installing dependencies..."
	@if command -v brew >/dev/null 2>&1; then \
		brew install jsoncpp; \
	elif command -v apt-get >/dev/null 2>&1; then \
		sudo apt-get install libjsoncpp-dev; \
	elif command -v yum >/dev/null 2>&1; then \
		sudo yum install jsoncpp-devel; \
	else \
		echo "Please install jsoncpp manually"; \
	fi

# Test the engine
test: $(TARGET)
	@echo "Testing C++ LOC Scanner Engine..."
	@echo '{"command":"version"}' | $(TARGET)
	@echo ""
	@echo "Testing project scan (if ../backup_old exists):"
	@if [ -d "../backup_old" ]; then \
		echo '{"command":"scan_project","params":{"project_path":"../backup_old"}}' | $(TARGET); \
	else \
		echo "No test directory found. Create a test project or use: $(TARGET) scan /path/to/project"; \
	fi

# Clean build files
clean:
	rm -rf $(BUILDDIR)

# Run in interactive mode
interactive: $(TARGET)
	$(TARGET)

# Install to system (optional)
install: $(TARGET)
	cp $(TARGET) /usr/local/bin/loc_scanner_engine
	chmod +x /usr/local/bin/loc_scanner_engine
	@echo "Installed to /usr/local/bin/loc_scanner_engine"

.PHONY: all clean test install-deps interactive install