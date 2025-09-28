// DOM Elements
const canvas = document.getElementById("canvas");
const settingsContent = document.getElementById("settingsContent");
const emptyState = document.getElementById("emptyState");
const duplicateBtn = document.getElementById("duplicateBtn");
const undoBtn = document.getElementById("undoBtn");
const redoBtn = document.getElementById("redoBtn");
const clearBtn = document.getElementById("clearBtn");
const addBlockBtn = document.getElementById("addBlockBtn");
const mobilePreviewBtn = document.getElementById("mobilePreviewBtn");
const helpBtn = document.getElementById("helpBtn");

// State management
let selectedBlock = null;
let history = [];
let historyIndex = -1;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Add blocks from sidebar
    document.querySelectorAll(".tool").forEach(tool => {
        tool.addEventListener("click", () => addBlock(tool.dataset.type));
    });

    // Template selection
    document.querySelectorAll(".template").forEach(template => {
        template.addEventListener("click", () => loadTemplate(template.dataset.template));
    });

    // Toolbar buttons
    addBlockBtn.addEventListener("click", () => {
        addBlock("paragraph");
    });

    clearBtn.addEventListener("click", clearCanvas);
    undoBtn.addEventListener("click", undo);
    redoBtn.addEventListener("click", redo);
    duplicateBtn.addEventListener("click", duplicateBlock);

    // Preview and export
    setupPreviewAndExport();

    // Initialize with empty state
    saveState();
});

// Add a new block to the canvas
function addBlock(type, content = null) {
    // Hide empty state if it's visible
    if (emptyState.style.display !== 'none') {
        emptyState.style.display = 'none';
    }

    let block = document.createElement("div");
    block.className = "block " + type;
    block.setAttribute("data-type", type);

    // Block controls
    let controls = document.createElement("div");
    controls.className = "block-controls";
    controls.innerHTML = `
        <button class="move-up" title="Move Up"><i class="fas fa-arrow-up"></i></button>
        <button class="move-down" title="Move Down"><i class="fas fa-arrow-down"></i></button>
        <button class="delete" title="Delete"><i class="fas fa-trash"></i></button>
    `;
    block.appendChild(controls);

    // Block content
    let contentDiv = document.createElement("div");
    contentDiv.className = "block-content";
    
    switch(type) {
        case "heading":
            contentDiv.innerHTML = `<h1 class="heading">${content || "Your Heading"}</h1>`;
            break;
        case "paragraph":
            contentDiv.innerHTML = `<p class="paragraph">${content || "Your paragraph text here..."}</p>`;
            break;
        case "image":
            contentDiv.innerHTML = `
                <div class="image">
                    <div class="image-placeholder">
                        <i class="fas fa-image"></i>
                        <p>Click to upload an image</p>
                        <input type="file" class="upload" accept="image/*" style="display:none;">
                    </div>
                </div>`;
            break;
        case "button":
            contentDiv.innerHTML = `<div class="button"><a href="#">${content || "Click Me"}</a></div>`;
            break;
        case "divider":
            contentDiv.innerHTML = `<div class="divider"></div>`;
            break;
        case "two-col":
            contentDiv.innerHTML = `<div class="two-col"><div>Column 1 content</div><div>Column 2 content</div></div>`;
            break;
        case "spacer":
            contentDiv.innerHTML = `<div class="spacer"></div>`;
            break;
        case "social":
            contentDiv.innerHTML = `
                <div class="social-links">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>`;
            break;
    }
    
    block.appendChild(contentDiv);
    canvas.appendChild(block);

    // Set up event handlers
    setupBlockEvents(block, type);

    // Select the new block
    selectBlock(block, type);
    
    // Save state for undo/redo
    saveState();
    
    return block;
}

// Set up events for a block
function setupBlockEvents(block, type) {
    // Delete button
    block.querySelector(".delete").addEventListener("click", (e) => {
        e.stopPropagation();
        block.remove();
        selectedBlock = null;
        settingsContent.innerHTML = "Select a block to edit its properties.";
        duplicateBtn.style.display = "none";
        
        // Show empty state if no blocks left
        if (canvas.querySelectorAll('.block').length === 0) {
            emptyState.style.display = 'block';
        }
        
        saveState();
    });

    // Move up button
    block.querySelector(".move-up").addEventListener("click", (e) => {
        e.stopPropagation();
        const prev = block.previousElementSibling;
        if (prev && prev.classList.contains('block')) {
            canvas.insertBefore(block, prev);
            saveState();
        }
    });

    // Move down button
    block.querySelector(".move-down").addEventListener("click", (e) => {
        e.stopPropagation();
        const next = block.nextElementSibling;
        if (next && next.classList.contains('block')) {
            canvas.insertBefore(next, block);
            saveState();
        }
    });

    // Select block on click
    block.addEventListener("click", (e) => {
        if (!e.target.closest(".block-controls")) {
            selectBlock(block, type);
        }
    });

    // Image upload handling
    if (type === "image") {
        const uploadInput = block.querySelector(".upload");
        const placeholder = block.querySelector(".image-placeholder");
        
        placeholder.addEventListener("click", () => {
            uploadInput.click();
        });
        
        uploadInput.addEventListener("change", function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    placeholder.innerHTML = `<img src="${e.target.result}" alt="Uploaded image">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Make block draggable
    makeDraggable(block);
}

// Make a block draggable
function makeDraggable(block) {
    block.setAttribute('draggable', true);
    
    block.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('text/plain', 'block');
        block.classList.add('dragging');
    });
    
    block.addEventListener('dragend', () => {
        block.classList.remove('dragging');
        document.querySelectorAll('.drag-over').forEach(el => {
            el.classList.remove('drag-over');
        });
    });
}

// Setup drag and drop for the canvas
canvas.addEventListener('dragover', (e) => {
    e.preventDefault();
    const afterElement = getDragAfterElement(canvas, e.clientY);
    const draggable = document.querySelector('.dragging');
    
    if (afterElement == null) {
        canvas.appendChild(draggable);
    } else {
        canvas.insertBefore(draggable, afterElement);
    }
});

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.block:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// Select a block for editing
function selectBlock(block, type) {
    // Deselect previously selected block
    if (selectedBlock) {
        selectedBlock.classList.remove('selected');
    }
    
    // Select new block
    selectedBlock = block;
    block.classList.add('selected');
    duplicateBtn.style.display = "flex";
    
    // Update settings panel based on block type
    settingsContent.innerHTML = "";
    
    switch(type) {
        case "heading":
            let heading = block.querySelector(".heading");
            settingsContent.innerHTML = `
                <label>Text</label>
                <input type="text" id="headingText" value="${heading.innerText}">
                <div class="settings-row">
                    <div>
                        <label>Color</label>
                        <input type="color" id="headingColor" value="${heading.style.color || '#000000'}">
                        <span class="color-preview" style="background-color:${heading.style.color || '#000000'}"></span>
                    </div>
                    <div>
                        <label>Size</label>
                        <select id="headingSize">
                            <option value="20px">Small</option>
                            <option value="24px" selected>Medium</option>
                            <option value="28px">Large</option>
                            <option value="32px">X-Large</option>
                        </select>
                    </div>
                </div>
                <label>Alignment</label>
                <select id="headingAlign">
                    <option value="left">Left</option>
                    <option value="center" selected>Center</option>
                    <option value="right">Right</option>
                </select>
            `;
            
            // Set current values
            document.getElementById('headingSize').value = heading.style.fontSize || '24px';
            document.getElementById('headingAlign').value = heading.style.textAlign || 'center';
            
            // Add event listeners
            document.getElementById('headingText').addEventListener('input', (e) => {
                heading.innerText = e.target.value;
            });
            
            document.getElementById('headingColor').addEventListener('input', (e) => {
                heading.style.color = e.target.value;
                document.querySelector('.color-preview').style.backgroundColor = e.target.value;
            });
            
            document.getElementById('headingSize').addEventListener('change', (e) => {
                heading.style.fontSize = e.target.value;
            });
            
            document.getElementById('headingAlign').addEventListener('change', (e) => {
                heading.style.textAlign = e.target.value;
            });
            break;
            
        case "paragraph":
            let paragraph = block.querySelector(".paragraph");
            settingsContent.innerHTML = `
                <label>Text</label>
                <textarea id="paragraphText">${paragraph.innerText}</textarea>
                <div class="settings-row">
                    <div>
                        <label>Color</label>
                        <input type="color" id="paragraphColor" value="${paragraph.style.color || '#000000'}">
                        <span class="color-preview" style="background-color:${paragraph.style.color || '#000000'}"></span>
                    </div>
                    <div>
                        <label>Size</label>
                        <select id="paragraphSize">
                            <option value="12px">Small</option>
                            <option value="14px" selected>Medium</option>
                            <option value="16px">Large</option>
                        </select>
                    </div>
                </div>
                <label>Alignment</label>
                <select id="paragraphAlign">
                    <option value="left" selected>Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                    <option value="justify">Justify</option>
                </select>
            `;
            
            // Set current values
            document.getElementById('paragraphSize').value = paragraph.style.fontSize || '14px';
            document.getElementById('paragraphAlign').value = paragraph.style.textAlign || 'left';
            
            // Add event listeners
            document.getElementById('paragraphText').addEventListener('input', (e) => {
                paragraph.innerText = e.target.value;
            });
            
            document.getElementById('paragraphColor').addEventListener('input', (e) => {
                paragraph.style.color = e.target.value;
                document.querySelector('.color-preview').style.backgroundColor = e.target.value;
            });
            
            document.getElementById('paragraphSize').addEventListener('change', (e) => {
                paragraph.style.fontSize = e.target.value;
            });
            
            document.getElementById('paragraphAlign').addEventListener('change', (e) => {
                paragraph.style.textAlign = e.target.value;
            });
            break;
            
        case "image":
            settingsContent.innerHTML = `
                <label>Image URL</label>
                <input type="url" id="imageUrl" placeholder="https://example.com/image.jpg">
                <label>Alternative Text</label>
                <input type="text" id="imageAlt" placeholder="Description of image">
                <label>Alignment</label>
                <select id="imageAlign">
                    <option value="left">Left</option>
                    <option value="center" selected>Center</option>
                    <option value="right">Right</option>
                </select>
                <label>Width</label>
                <input type="text" id="imageWidth" placeholder="100% or 300px">
            `;
            
            // Add event listeners
            document.getElementById('imageUrl').addEventListener('input', (e) => {
                const img = block.querySelector('img');
                if (img) {
                    img.src = e.target.value;
                } else {
                    const placeholder = block.querySelector('.image-placeholder');
                    placeholder.innerHTML = `<img src="${e.target.value}" alt="${document.getElementById('imageAlt').value}">`;
                }
            });
            
            document.getElementById('imageAlt').addEventListener('input', (e) => {
                const img = block.querySelector('img');
                if (img) img.alt = e.target.value;
            });
            
            document.getElementById('imageAlign').addEventListener('change', (e) => {
                const imgContainer = block.querySelector('.image');
                imgContainer.style.textAlign = e.target.value;
            });
            
            document.getElementById('imageWidth').addEventListener('input', (e) => {
                const img = block.querySelector('img');
                if (img) img.style.width = e.target.value;
            });
            break;
            
        case "button":
            let button = block.querySelector("a");
            settingsContent.innerHTML = `
                <label>Button Text</label>
                <input type="text" id="buttonText" value="${button.innerText}">
                <label>Button Link</label>
                <input type="url" id="buttonLink" value="${button.href}">
                <div class="settings-row">
                    <div>
                        <label>Background Color</label>
                        <input type="color" id="buttonBgColor" value="${button.style.backgroundColor || '#0066ff'}">
                        <span class="color-preview" style="background-color:${button.style.backgroundColor || '#0066ff'}"></span>
                    </div>
                    <div>
                        <label>Text Color</label>
                        <input type="color" id="buttonTextColor" value="${button.style.color || '#ffffff'}">
                        <span class="color-preview" style="background-color:${button.style.color || '#ffffff'}"></span>
                    </div>
                </div>
                <label>Alignment</label>
                <select id="buttonAlign">
                    <option value="left">Left</option>
                    <option value="center" selected>Center</option>
                    <option value="right">Right</option>
                </select>
            `;
            
            // Add event listeners
            document.getElementById('buttonText').addEventListener('input', (e) => {
                button.innerText = e.target.value;
            });
            
            document.getElementById('buttonLink').addEventListener('input', (e) => {
                button.href = e.target.value;
            });
            
            document.getElementById('buttonBgColor').addEventListener('input', (e) => {
                button.style.backgroundColor = e.target.value;
                document.querySelectorAll('.color-preview')[0].style.backgroundColor = e.target.value;
            });
            
            document.getElementById('buttonTextColor').addEventListener('input', (e) => {
                button.style.color = e.target.value;
                document.querySelectorAll('.color-preview')[1].style.backgroundColor = e.target.value;
            });
            
            document.getElementById('buttonAlign').addEventListener('change', (e) => {
                const buttonContainer = block.querySelector('.button');
                buttonContainer.style.textAlign = e.target.value;
            });
            break;
            
        case "divider":
            let divider = block.querySelector(".divider");
            settingsContent.innerHTML = `
                <label>Style</label>
                <select id="dividerStyle">
                    <option value="solid">Solid</option>
                    <option value="dashed">Dashed</option>
                    <option value="dotted">Dotted</option>
                    <option value="double">Double</option>
                </select>
                <div class="settings-row">
                    <div>
                        <label>Color</label>
                        <input type="color" id="dividerColor" value="${divider.style.borderColor || '#dddddd'}">
                        <span class="color-preview" style="background-color:${divider.style.borderColor || '#dddddd'}"></span>
                    </div>
                    <div>
                        <label>Thickness</label>
                        <select id="dividerThickness">
                            <option value="1px">Thin</option>
                            <option value="2px" selected>Medium</option>
                            <option value="3px">Thick</option>
                        </select>
                    </div>
                </div>
            `;
            
            // Set current values
            const borderStyle = divider.style.borderTopStyle || 'solid';
            document.getElementById('dividerStyle').value = borderStyle;
            
            const borderWidth = divider.style.borderTopWidth || '2px';
            document.getElementById('dividerThickness').value = borderWidth;
            
            // Add event listeners
            document.getElementById('dividerStyle').addEventListener('change', (e) => {
                divider.style.borderTopStyle = e.target.value;
            });
            
            document.getElementById('dividerColor').addEventListener('input', (e) => {
                divider.style.borderColor = e.target.value;
                document.querySelector('.color-preview').style.backgroundColor = e.target.value;
            });
            
            document.getElementById('dividerThickness').addEventListener('change', (e) => {
                divider.style.borderTopWidth = e.target.value;
            });
            break;
            
        case "two-col":
            settingsContent.innerHTML = `
                <label>Column Gap</label>
                <input type="text" id="colGap" value="15px">
                <label>Column Background</label>
                <input type="color" id="colBg" value="#ffffff">
                <span class="color-preview" style="background-color:#ffffff"></span>
            `;
            
            // Add event listeners
            document.getElementById('colGap').addEventListener('input', (e) => {
                const twoCol = block.querySelector('.two-col');
                twoCol.style.gap = e.target.value;
            });
            
            document.getElementById('colBg').addEventListener('input', (e) => {
                const cols = block.querySelectorAll('.two-col > div');
                cols.forEach(col => {
                    col.style.backgroundColor = e.target.value;
                });
                document.querySelector('.color-preview').style.backgroundColor = e.target.value;
            });
            break;
            
        case "social":
            settingsContent.innerHTML = `
                <label>Social Platforms</label>
                <div>
                    <input type="checkbox" id="facebook" checked> <label for="facebook" style="display:inline;">Facebook</label><br>
                    <input type="checkbox" id="twitter" checked> <label for="twitter" style="display:inline;">Twitter</label><br>
                    <input type="checkbox" id="instagram" checked> <label for="instagram" style="display:inline;">Instagram</label><br>
                    <input type="checkbox" id="linkedin" checked> <label for="linkedin" style="display:inline;">LinkedIn</label><br>
                    <input type="checkbox" id="youtube"> <label for="youtube" style="display:inline;">YouTube</label>
                </div>
                <label>Icon Color</label>
                <input type="color" id="socialColor" value="#0066ff">
                <span class="color-preview" style="background-color:#0066ff"></span>
                <label>Alignment</label>
                <select id="socialAlign">
                    <option value="left">Left</option>
                    <option value="center" selected>Center</option>
                    <option value="right">Right</option>
                </select>
            `;
            
            // Add event listeners
            document.getElementById('socialColor').addEventListener('input', (e) => {
                const links = block.querySelectorAll('.social-links a');
                links.forEach(link => {
                    link.style.backgroundColor = e.target.value;
                });
                document.querySelector('.color-preview').style.backgroundColor = e.target.value;
            });
            
            document.getElementById('socialAlign').addEventListener('change', (e) => {
                const socialContainer = block.querySelector('.social-links');
                socialContainer.style.justifyContent = e.target.value === 'left' ? 'flex-start' : 
                                                    e.target.value === 'center' ? 'center' : 'flex-end';
            });
            
            // Platform selection
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSocialLinks);
            });
            
            function updateSocialLinks() {
                const socialContainer = block.querySelector('.social-links');
                socialContainer.innerHTML = '';
                
                const platforms = [
                    { id: 'facebook', icon: 'facebook-f', url: '#' },
                    { id: 'twitter', icon: 'twitter', url: '#' },
                    { id: 'instagram', icon: 'instagram', url: '#' },
                    { id: 'linkedin', icon: 'linkedin-in', url: '#' },
                    { id: 'youtube', icon: 'youtube', url: '#' }
                ];
                
                platforms.forEach(platform => {
                    if (document.getElementById(platform.id).checked) {
                        const link = document.createElement('a');
                        link.href = platform.url;
                        link.title = platform.id.charAt(0).toUpperCase() + platform.id.slice(1);
                        link.innerHTML = `<i class="fab fa-${platform.icon}"></i>`;
                        socialContainer.appendChild(link);
                    }
                });
            }
            break;
            
        case "spacer":
            let spacer = block.querySelector(".spacer");
            settingsContent.innerHTML = `
                <label>Height</label>
                <input type="text" id="spacerHeight" value="30px">
            `;
            
            // Add event listener
            document.getElementById('spacerHeight').addEventListener('input', (e) => {
                spacer.style.height = e.target.value;
            });
            break;
    }
}

// Duplicate the selected block
function duplicateBlock() {
    if (!selectedBlock) return;
    
    const type = selectedBlock.getAttribute('data-type');
    let content = null;
    
    // Extract content based on block type
    switch(type) {
        case "heading":
            content = selectedBlock.querySelector('.heading').innerText;
            break;
        case "paragraph":
            content = selectedBlock.querySelector('.paragraph').innerText;
            break;
        case "button":
            content = selectedBlock.querySelector('a').innerText;
            break;
    }
    
    const newBlock = addBlock(type, content);
    
    // Copy styles if applicable
    if (type === "heading") {
        const original = selectedBlock.querySelector('.heading');
        const duplicate = newBlock.querySelector('.heading');
        duplicate.style.color = original.style.color;
        duplicate.style.fontSize = original.style.fontSize;
        duplicate.style.textAlign = original.style.textAlign;
    } else if (type === "paragraph") {
        const original = selectedBlock.querySelector('.paragraph');
        const duplicate = newBlock.querySelector('.paragraph');
        duplicate.style.color = original.style.color;
        duplicate.style.fontSize = original.style.fontSize;
        duplicate.style.textAlign = original.style.textAlign;
    } else if (type === "button") {
        const original = selectedBlock.querySelector('a');
        const duplicate = newBlock.querySelector('a');
        duplicate.style.backgroundColor = original.style.backgroundColor;
        duplicate.style.color = original.style.color;
        newBlock.querySelector('.button').style.textAlign = selectedBlock.querySelector('.button').style.textAlign;
    }
    
    // Insert after the selected block
    selectedBlock.parentNode.insertBefore(newBlock, selectedBlock.nextSibling);
    
    // Select the new block
    selectBlock(newBlock, type);
}

// Clear the entire canvas
function clearCanvas() {
    if (confirm("Are you sure you want to clear the entire newsletter? This action cannot be undone.")) {
        canvas.innerHTML = '';
        emptyState.style.display = 'block';
        selectedBlock = null;
        settingsContent.innerHTML = "Select a block to edit its properties.";
        duplicateBtn.style.display = "none";
        saveState();
    }
}

// Load a template
function loadTemplate(templateName) {
    clearCanvas();
    
    switch(templateName) {
        case "simple":
            addBlock("heading", "Welcome to Our Newsletter");
            addBlock("paragraph", "This is a simple newsletter template. You can customize it by adding your own content.");
            addBlock("divider");
            addBlock("heading", "Latest Updates");
            addBlock("paragraph", "Here you can share your latest news, updates, or announcements with your subscribers.");
            addBlock("button", "Learn More");
            addBlock("spacer");
            addBlock("social");
            break;
            
        case "promotional":
            addBlock("heading", "Special Offer Inside!");
            addBlock("paragraph", "Don't miss out on our limited-time promotion. Act now to get exclusive discounts.");
            addBlock("image");
            addBlock("paragraph", "Use this section to highlight your promotion details and benefits.");
            addBlock("button", "Shop Now");
            addBlock("divider");
            addBlock("two-col");
            addBlock("social");
            break;
            
        case "news":
            addBlock("heading", "Company News & Updates");
            addBlock("paragraph", "Stay informed with the latest developments from our company.");
            addBlock("divider");
            addBlock("heading", "Recent Achievements");
            addBlock("paragraph", "Share your recent milestones, awards, or important company news here.");
            addBlock("divider");
            addBlock("heading", "Upcoming Events");
            addBlock("paragraph", "Let your subscribers know about upcoming webinars, conferences, or other events.");
            addBlock("button", "RSVP Now");
            addBlock("social");
            break;
            
        case "event":
            addBlock("heading", "You're Invited!");
            addBlock("paragraph", "Join us for an exclusive event that you won't want to miss.");
            addBlock("image");
            addBlock("paragraph", "Include all the important details about your event: date, time, location, and what attendees can expect.");
            addBlock("button", "Register Now");
            addBlock("divider");
            addBlock("two-col");
            addBlock("social");
            break;
    }
    
    showToast(`"${templateName.charAt(0).toUpperCase() + templateName.slice(1)}" template loaded successfully!`);
}

// Undo/Redo functionality
function saveState() {
    // Remove any future states if we're not at the end of history
    if (historyIndex < history.length - 1) {
        history = history.slice(0, historyIndex + 1);
    }
    
    // Save current state
    history.push(canvas.innerHTML);
    historyIndex++;
    
    // Limit history size
    if (history.length > 50) {
        history.shift();
        historyIndex--;
    }
    
    // Update undo/redo buttons
    updateHistoryButtons();
}

function undo() {
    if (historyIndex > 0) {
        historyIndex--;
        canvas.innerHTML = history[historyIndex];
        updateHistoryButtons();
        
        // Hide empty state if content exists
        if (canvas.querySelectorAll('.block').length > 0) {
            emptyState.style.display = 'none';
        } else {
            emptyState.style.display = 'block';
        }
    }
}

function redo() {
    if (historyIndex < history.length - 1) {
        historyIndex++;
        canvas.innerHTML = history[historyIndex];
        updateHistoryButtons();
        
        // Hide empty state if content exists
        if (canvas.querySelectorAll('.block').length > 0) {
            emptyState.style.display = 'none';
        } else {
            emptyState.style.display = 'block';
        }
    }
}

function updateHistoryButtons() {
    undoBtn.disabled = historyIndex <= 0;
    redoBtn.disabled = historyIndex >= history.length - 1;
}

// Preview and Export functionality
function setupPreviewAndExport() {
    const previewBtn = document.getElementById("previewBtn");
    const exportBtn = document.getElementById("exportBtn");
    const saveBtn = document.getElementById("saveBtn");
    const closeModal = document.getElementById("closeModal");
    const modal = document.getElementById("modal");
    const modalTabs = document.querySelectorAll(".modal-tab");
    
    // Preview button
    previewBtn.addEventListener("click", () => {
        const previewArea = document.getElementById("previewArea");
        previewArea.innerHTML = canvas.innerHTML;
        
        // Remove block controls from preview
        previewArea.querySelectorAll('.block-controls').forEach(el => el.remove());
        
        // Show modal
        modal.style.display = "flex";
        
        // Switch to preview tab
        switchTab('preview');
    });
    
    // Export button
    exportBtn.addEventListener("click", () => {
        let content = canvas.innerHTML;
        
        // Remove block controls from export
        content = content.replace(/<div class="block-controls">[\s\S]*?<\/div>/g, '');
        
        // Create a complete HTML document
        const fullHtml = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Newsletter</title>
<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background: #f5f5f5; }
    .newsletter { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .block { margin-bottom: 20px; }
    .heading { font-size: 24px; font-weight: bold; margin: 0 0 10px 0; text-align: center; }
    .paragraph { font-size: 14px; margin: 0 0 10px 0; }
    .image img { max-width: 100%; border-radius: 4px; display: block; margin: 0 auto; }
    .button a { display: inline-block; background: #0066ff; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold; text-align: center; }
    .divider { border-top: 1px solid #ddd; margin: 15px 0; }
    .two-col { display: flex; gap: 15px; }
    .two-col > div { flex: 1; border: 1px dashed #ddd; padding: 15px; border-radius: 4px; }
    .social-links { display: flex; justify-content: center; gap: 15px; margin: 10px 0; }
    .social-links a { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #0066ff; color: white; border-radius: 50%; text-decoration: none; }
    .spacer { height: 30px; }
    @media (max-width: 600px) {
        .two-col { flex-direction: column; }
        .newsletter { padding: 15px; }
    }
</style>
</head>
<body>
<div class="newsletter">
${content}
</div>
</body>
</html>`;
        
        let blob = new Blob([fullHtml], {type:"text/html"});
        let a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = "newsletter.html";
        a.click();
        URL.revokeObjectURL(a.href);
        
        showToast("Newsletter exported successfully!");
    });
    
    // Save draft button
    saveBtn.addEventListener("click", () => {
        const content = canvas.innerHTML;
        localStorage.setItem('newsletterDraft', content);
        showToast("Draft saved successfully!");
    });
    
    // Load draft on page load if exists
    const savedDraft = localStorage.getItem('newsletterDraft');
    if (savedDraft && savedDraft !== '<div class="block-placeholder" id="emptyState">...</div>') {
        if (confirm("A saved draft was found. Would you like to load it?")) {
            canvas.innerHTML = savedDraft;
            emptyState.style.display = 'none';
            
            // Reattach events to all blocks
            canvas.querySelectorAll('.block').forEach(block => {
                const type = block.getAttribute('data-type');
                setupBlockEvents(block, type);
            });
            
            saveState();
        }
    }
    
    // Close modal
    closeModal.addEventListener("click", () => modal.style.display = "none");
    
    // Modal tabs
    modalTabs.forEach(tab => {
        tab.addEventListener("click", () => {
            const tabName = tab.getAttribute('data-tab');
            switchTab(tabName);
        });
    });
    
    // Tab switching function
    function switchTab(tabName) {
        // Update active tab
        modalTabs.forEach(tab => {
            tab.classList.toggle('active', tab.getAttribute('data-tab') === tabName);
        });
        
        // Show corresponding content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.id === tabName + 'Tab');
        });
        
        // Generate code if needed
        if (tabName === 'html') {
            let htmlContent = canvas.innerHTML;
            htmlContent = htmlContent.replace(/<div class="block-controls">[\s\S]*?<\/div>/g, '');
            document.getElementById('htmlCode').textContent = htmlContent;
        } else if (tabName === 'css') {
            document.getElementById('cssCode').textContent = `/* Newsletter CSS */
.newsletter {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.block {
    margin-bottom: 20px;
}

.heading {
    font-size: 24px;
    font-weight: bold;
    margin: 0 0 10px 0;
    text-align: center;
}

.paragraph {
    font-size: 14px;
    margin: 0 0 10px 0;
    line-height: 1.6;
}

.image img {
    max-width: 100%;
    border-radius: 4px;
    display: block;
    margin: 0 auto;
}

.button a {
    display: inline-block;
    background: #0066ff;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
}

.divider {
    border-top: 1px solid #ddd;
    margin: 15px 0;
}

.two-col {
    display: flex;
    gap: 15px;
}

.two-col > div {
    flex: 1;
    border: 1px dashed #ddd;
    padding: 15px;
    border-radius: 4px;
}

.social-links {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin: 10px 0;
}

.social-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #0066ff;
    color: white;
    border-radius: 50%;
    text-decoration: none;
}

.spacer {
    height: 30px;
}

@media (max-width: 600px) {
    .two-col {
        flex-direction: column;
    }
    
    .newsletter {
        padding: 15px;
    }
}`;
        }
    }
}

// Toast notification
function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    toastMessage.textContent = message;
    
    if (isError) {
        toast.classList.add('error');
    } else {
        toast.classList.remove('error');
    }
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Help button
helpBtn.addEventListener('click', () => {
    alert(`Newsletter Builder Pro Help:

1. ADDING ELEMENTS: Click on any element in the sidebar to add it to your newsletter.

2. EDITING ELEMENTS: Click on any block in the newsletter to edit its properties in the settings panel.

3. REORDERING: Use the up/down arrows in the block controls or drag and drop blocks to reorder them.

4. TEMPLATES: Start with a pre-designed template from the Templates section.

5. PREVIEW: Use the Preview button to see how your newsletter will look.

6. EXPORT: Click Export HTML to download your newsletter as an HTML file.

7. SAVE: Use Save Draft to save your work and continue later.

For more advanced customization, you can directly edit the HTML/CSS in the preview modal.`);
});

// Mobile preview button
mobilePreviewBtn.addEventListener('click', () => {
    const previewArea = document.getElementById("previewArea");
    previewArea.innerHTML = canvas.innerHTML;
    
    // Remove block controls from preview
    previewArea.querySelectorAll('.block-controls').forEach(el => el.remove());
    
    // Show modal
    const modal = document.getElementById("modal");
    modal.style.display = "flex";
    
    // Switch to preview tab
    document.querySelectorAll('.modal-tab').forEach(tab => {
        if (tab.getAttribute('data-tab') === 'preview') {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
    
    document.querySelectorAll('.tab-content').forEach(content => {
        if (content.id === 'previewTab') {
            content.classList.add('active');
        } else {
            content.classList.remove('active');
        }
    });
    
    // Add mobile styling
    const previewContainer = document.querySelector('.preview-container');
    previewContainer.style.maxWidth = '400px';
    previewContainer.style.margin = '0 auto';
    previewContainer.style.border = '2px solid #ccc';
    previewContainer.style.borderRadius = '20px';
    previewContainer.style.padding = '10px';
    previewContainer.style.background = '#f0f0f0';
});

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    const modal = document.getElementById("modal");
    if (e.target === modal) {
        modal.style.display = "none";
    }
});